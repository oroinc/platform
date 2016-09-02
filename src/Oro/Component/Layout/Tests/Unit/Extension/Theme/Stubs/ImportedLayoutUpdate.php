<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\IsApplicableLayoutUpdateInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

class ImportedLayoutUpdate implements LayoutUpdateInterface, LayoutUpdateImportInterface
{
    public function getImport()
    {
    }

    public function setImport(LayoutUpdateImport $import)
    {
    }

    public function setParentUpdate(ImportsAwareLayoutUpdateInterface $parentLayoutUpdate)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
    }
}
