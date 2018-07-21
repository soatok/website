<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Contract;

use ParagonIE\HiddenString\HiddenString;

/**
 * Interface CryptographicKeyInterface
 * @package Soatok\Website\Engine\Contract
 */
interface CryptographicKeyInterface
{
    /**
     * @return HiddenString
     */
    public function getHiddenString(): HiddenString;

    /**
     * Hazardous Material: Don't use this method recklessly.
     *
     * @return string
     */
    public function getRawKeyMaterial(): string;
}
