<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Cryptography\Key;

use Soatok\Website\Engine\Contract\CryptographicKeyInterface;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class SymmetricKey
 * @package Soatok\Website\Engine\Cryptography\Key
 */
final class SymmetricKey implements CryptographicKeyInterface
{
    /** @var HiddenString $key */
    private $key;

    /**
     * @return self
     */
    public static function generate(): self
    {
        $randomKey = \random_bytes(32);
        $hiddenString = new HiddenString($randomKey);
        \sodium_memzero($randomKey);
        return new self($hiddenString);
    }

    /**
     * SymmetricKey constructor.
     *
     * @param HiddenString $key
     */
    public function __construct(HiddenString $key)
    {
        $this->key = $key;
    }

    /**
     * @return HiddenString
     */
    public function getHiddenString(): HiddenString
    {
        return $this->key;
    }

    /**
     * Hazardous Material: Don't use this method recklessly.
     *
     * @return string
     */
    public function getRawKeyMaterial(): string
    {
        return $this->key->getString();
    }
}
