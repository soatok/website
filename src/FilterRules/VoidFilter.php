<?php
declare(strict_types=1);
namespace Soatok\Website\FilterRules;

use ParagonIE\Ionizer\InputFilterContainer;

/**
 * Class VoidFilter
 * @package Soatok\Website\FilterRules
 */
class VoidFilter extends InputFilterContainer
{
    /**
     * VoidFilter constructor.
     *
     * NOP. We don't have any input filters here.
     */
    public function __construct()
    {
    }
}
