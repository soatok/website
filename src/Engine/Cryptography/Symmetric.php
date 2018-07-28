<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Cryptography;

use ParagonIE\ConstantTime\{
    Base64UrlSafe,
    Binary,
    Hex
};
use ParagonIE\HiddenString\HiddenString;
use ParagonIE_Sodium_Compat as NaCl;
use Soatok\Website\Engine\Cryptography\Key\SymmetricKey;
use Soatok\Website\Engine\Exceptions\CryptoException;

/**
 * Class Symmetric
 * @package Soatok\Website\Engine\Cryptography
 */
abstract class Symmetric
{
    /*
     * The last three digits represent version information, although no change
     * is anticipated at this time.
     */
    const HEADER = "furry100";

    /*
     * Prehash the symmetric key with this constant to get a separate key for
     * message authentication
     */
    const AUTH_DOMAIN_SEPARATION = "S0470K::domain-separation4authKz";

    /*
     * If we need to change protocol versions, we'll greenlight the new version
     * headers in this hard-coded constant:
     */
    const ALLOWED_HEADERS = ["furry100"];

    /**
     * @param string $message
     * @param SymmetricKey $key
     * @param bool $raw
     *
     * @return string
     * @throws \SodiumException
     */
    public static function auth(
        string $message,
        SymmetricKey $key,
        bool $raw = false
    ): string {
        $subKey = NaCl::crypto_generichash(
            $key->getRawKeyMaterial(),
            self::AUTH_DOMAIN_SEPARATION
        );
        $mac = NaCl::crypto_auth($message, $subKey);
        \sodium_memzero($subKey);
        if ($raw) {
            return $mac;
        }
        return NaCl::bin2hex($mac);
    }

    /**
     * @param HiddenString $message
     * @param SymmetricKey $key
     *
     * @return string
     * @throws \SodiumException
     */
    public static function encrypt(
        HiddenString $message,
        SymmetricKey $key
    ): string {
        return self::encryptWithAd($message, $key);
    }

    /**
     * @param HiddenString $message
     * @param SymmetricKey $key
     * @param string $additionalData
     *
     * @return string
     * @throws \SodiumException
     */
    public static function encryptWithAd(
        HiddenString $message,
        SymmetricKey $key,
        string $additionalData = ''
    ): string {
        $nonce = \random_bytes(24);

        // This is IND-CCA3 secure:
        $ciphertext = NaCl::crypto_aead_xchacha20poly1305_ietf_encrypt(
            $message->getString(),
            self::HEADER . $nonce . $additionalData,
            $nonce,
            $key->getRawKeyMaterial()
        );
        return self::HEADER . Base64UrlSafe::encode($nonce . $ciphertext);
    }

    /**
     * @param string $encrypted
     * @param SymmetricKey $key
     *
     * @return HiddenString
     * @throws CryptoException
     * @throws \SodiumException
     */
    public static function decrypt(
        string $encrypted,
        SymmetricKey $key
    ): HiddenString {
        return self::decryptWithAd($encrypted, $key);
    }

    /**
     * @param string $message
     * @param SymmetricKey $key
     * @param string $mac
     * @param bool $macIsRaw
     *
     * @return bool
     * @throws \SodiumException
     */
    public static function verify(
        string $message,
        SymmetricKey $key,
        string $mac,
        bool $macIsRaw = false
    ): bool {
        if (!$macIsRaw) {
            $mac = Hex::decode($mac);
        }

        $subKey = NaCl::crypto_generichash(
            $key->getRawKeyMaterial(),
            self::AUTH_DOMAIN_SEPARATION
        );
        $calc = NaCl::crypto_auth($message, $subKey);
        \sodium_memzero($subKey);
        return \hash_equals($calc, $mac);
    }

    /**
     * @param string $encrypted
     * @param SymmetricKey $key
     * @param string $additionalData
     *
     * @return HiddenString
     * @throws CryptoException
     * @throws \SodiumException
     */
    public static function decryptWithAd(
        string $encrypted,
        SymmetricKey $key,
        string $additionalData = ''
    ): HiddenString {
        if (Binary::safeStrlen($encrypted) < 8) {
            throw new CryptoException(
                'String too short to be valid ciphertext'
            );
        }
        $header = Binary::safeSubstr($encrypted, 0, 8);
        if (!\in_array($header, self::ALLOWED_HEADERS, true)) {
            throw new CryptoException('Invalid message header');
        }
        $encoded = Binary::safeSubstr($encrypted, 8);
        $decoded = Base64UrlSafe::decode($encoded);
        if (Binary::safeStrlen($decoded) < 40) {
            throw new CryptoException(
                'Decoded string too short to be valid ciphertext'
            );
        }

        $nonce = Binary::safeSubstr($decoded, 0, 24);
        $ciphertext = Binary::safeSubstr($decoded, 24);

        $plaintext = NaCl::crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            self::HEADER . $nonce . $additionalData,
            $nonce,
            $key->getRawKeyMaterial()
        );
        if (!\is_string($plaintext)) {
            throw new CryptoException('Invalid message body');
        }
        $hiddenString = new HiddenString($plaintext);
        \sodium_memzero($plaintext);
        return $hiddenString;
    }
}
