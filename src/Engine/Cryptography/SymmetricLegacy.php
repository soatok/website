<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Cryptography;

use Soatok\DholeCrypto\Symmetric;

/**
 * Class SymmetricLegacy
 * @package Soatok\Website\Engine\Cryptography
 */
class SymmetricLegacy extends Symmetric
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
}
