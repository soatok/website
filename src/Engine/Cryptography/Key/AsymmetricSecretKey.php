<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Cryptography\Key;

use Soatok\Website\Engine\Contract\CryptographicKeyInterface;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class SymmetricKey
 * @package Soatok\Website\Engine\Cryptography\Key
 */
final class AsymmetricSecretKey implements CryptographicKeyInterface
{
    /** @var HiddenString $secret */
    private $secret;

    /** @var AsymmetricPublicKey $public */
    private $public;

    /**
     * @return self
     */
    public static function generate(): self
    {
        $keypair = \sodium_crypto_sign_keypair();

        $sk = new HiddenString(
            \sodium_crypto_sign_secretkey($keypair)
        );
        $pk_hs = new HiddenString(
            \sodium_crypto_sign_publickey($keypair)
        );

        \sodium_memzero($keypair);
        $pk = new AsymmetricPublicKey($pk_hs);
        return new self($sk, $pk);
    }

    /**
     * AsymmetricSecretKey constructor.
     *
     * @param HiddenString $sk
     * @param AsymmetricPublicKey|null $pk
     */
    public function __construct(
        HiddenString $sk,
        ?AsymmetricPublicKey $pk = null
    ) {
        $this->secret = $sk;
        if (!$pk) {
            $rawSecret = $sk->getString();
            $pk_hs = new HiddenString(
                \sodium_crypto_sign_publickey_from_secretkey($rawSecret)
            );
            \sodium_memzero($rawSecret);
            $pk = new AsymmetricPublicKey($pk_hs);
        }
        $this->public = $pk;
    }

    /**
     * @return AsymmetricPublicKey
     */
    public function getPublicKey(): AsymmetricPublicKey
    {
        return $this->public;
    }

    /**
     * @return HiddenString
     */
    public function getHiddenString(): HiddenString
    {
        return $this->secret;
    }

    /**
     * Hazardous Material: Don't use this method recklessly.
     *
     * @return string
     */
    public function getRawKeyMaterial(): string
    {
        return $this->secret->getString();
    }
}
