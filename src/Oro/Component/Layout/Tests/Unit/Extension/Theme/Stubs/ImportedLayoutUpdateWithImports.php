<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

class ImportedLayoutUpdateWithImports implements
    LayoutUpdateInterface,
    LayoutUpdateImportInterface,
    ImportsAwareLayoutUpdateInterface
{
    public function setImport(LayoutUpdateImport $import)
    {
    }

    /**
     * @return array
     */
    public function getImports()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
    }
}
