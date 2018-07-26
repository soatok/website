<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Cryptography\Key;

use Soatok\Website\Engine\Contract\CryptographicKeyInterface;
use ParagonIE\HiddenString\HiddenString;

/**
 * Class SymmetricKey
 * @package Soatok\Website\Engine\Cryptography\Key
 */
final class AsymmetricPublicKey implements CryptographicKeyInterface
{
    /** @var HiddenString $public */
    private $public;

    /**
     * AsymmetricPublicKey constructor.
     *
     * @param HiddenString $pk
     */
    public function __construct(HiddenString $pk)
    {
        $this->public = $pk;
    }

    /**
     * @return HiddenString
     */
    public function getHiddenString(): HiddenString
    {
        return $this->public;
    }

    /**
     * Hazardous Material: Don't use this method recklessly.
     *
     * @return string
     */
    public function getRawKeyMaterial(): string
    {
        return $this->public->getString();
    }
}
