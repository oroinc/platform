<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ImportsAwareLayoutUpdateInterface;
use Oro\Component\Layout\IsApplicableLayoutUpdateInterface;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;
use Oro\Component\Layout\LayoutUpdateInterface;

class NotApplicableImportAwareLayoutUpdateStub implements
    LayoutUpdateInterface,
    ImportsAwareLayoutUpdateInterface,
    IsApplicableLayoutUpdateInterface
{
    #[\Override]
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
    }

    #[\Override]
    public function getImports()
    {
        return [new ImportedLayoutUpdate()];
    }

    #[\Override]
    public function isApplicable(ContextInterface $context)
    {
        return false;
    }
}
