<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutUpdateImportInterface;
use Oro\Component\Layout\LayoutUpdateInterface;
use Oro\Component\Layout\Model\LayoutUpdateImport;

class ImportedLayoutUpdate implements LayoutUpdateInterface, LayoutUpdateImportInterface
{
    #[\Override]
    public function getImport()
    {
    }

    #[\Override]
    public function setImport(LayoutUpdateImport $import)
    {
    }

    #[\Override]
    public function setParentUpdate(ImportsAwareLayoutUpdateInterface $parentLayoutUpdate)
    {
    }

    #[\Override]
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
    }
}
