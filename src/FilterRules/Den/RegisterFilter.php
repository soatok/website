<?php
declare(strict_types=1);
namespace Soatok\Website\FilterRules\Den;

use ParagonIE\Ionizer\Filter\{
    Special\EmailAddressFilter,
    StringFilter
};
use ParagonIE\Ionizer\InputFilterContainer;

/**
 * Class RegisterFilter
 * @package Soatok\Website\FilterRules\Den
 */
class RegisterFilter extends InputFilterContainer
{
    public function __construct()
    {
        $this
            ->addFilter('username', new StringFilter())
            ->addFilter('email', new EmailAddressFilter())
            ->addFilter('passphrase', new StringFilter())
            ->addFilter('authcode', new StringFilter());
    }
}
