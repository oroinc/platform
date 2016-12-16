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
    /**
     * {@inheritdoc}
     */
    public function updateLayout(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getImports()
    {
        return [new ImportedLayoutUpdate()];
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContextInterface $context)
    {
        return false;
    }
}
