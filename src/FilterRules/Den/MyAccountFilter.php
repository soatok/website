<?php
declare(strict_types=1);
namespace Soatok\Website\FilterRules\Den;

use ParagonIE\Ionizer\Filter\BoolFilter;
use ParagonIE\Ionizer\Filter\Special\EmailAddressFilter;
use ParagonIE\Ionizer\Filter\StringFilter;
use ParagonIE\Ionizer\InputFilterContainer;

/**
 * Class MyAccountFilter
 * @package Soatok\Website\FilterRules\Den
 */
class MyAccountFilter extends InputFilterContainer
{
    public function __construct()
    {
        $this
            ->addFilter('email', new EmailAddressFilter())
            ->addFilter('passphrase', new StringFilter())
            ->addFilter('2fa-toggle', new BoolFilter())
            ->addFilter('authcode', new StringFilter());
    }
}
