<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutUpdateInterface;

class LayoutUpdateWithImports implements LayoutUpdateInterface, ImportsAwareLayoutUpdateInterface
{
    /**
     * @var array
     */
    private $imports;

    /**
     * @param mixed $imports
     */
    public function __construct($imports)
    {
        $this->imports = $imports;
    }

    /**
     * @return array
     */
    public function getImports()
    {
        return $this->imports;
    }

    /**
     * {@inheritdoc}
     */
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
    }
}
