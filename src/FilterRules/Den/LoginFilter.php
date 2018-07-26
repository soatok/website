<?php
declare(strict_types=1);
namespace Soatok\Website\FilterRules\Den;

use ParagonIE\Ionizer\Filter\BoolFilter;
use ParagonIE\Ionizer\Filter\StringFilter;
use ParagonIE\Ionizer\InputFilterContainer;

/**
 * Class LoginFilter
 * @package Soatok\Website\FilterRules\Den
 */
class LoginFilter extends InputFilterContainer
{
    public function __construct()
    {
        $this
            ->addFilter('username', new StringFilter())
            ->addFilter('passphrase', new StringFilter())
            ->addFilter('remember', new BoolFilter());
    }
}
