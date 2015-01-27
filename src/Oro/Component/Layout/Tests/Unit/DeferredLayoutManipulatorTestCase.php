<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutDataBuilder;
use Oro\Component\Layout\LayoutViewFactory;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class DeferredLayoutManipulatorTestCase extends LayoutTestCase
{
    /** @var BlockTypeFactoryStub */
    protected $blockTypeFactory;

    /** @var LayoutContext */
    protected $context;

    /** @var LayoutDataBuilder */
    protected $layoutDataBuilder;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    /** @var LayoutViewFactory */
    protected $layoutViewFactory;

    protected function setUp()
    {
        $this->context           = new LayoutContext();
        $this->layoutDataBuilder = new LayoutDataBuilder();
        $this->blockTypeFactory  = new BlockTypeFactoryStub();
        $blockTypeRegistry       = new BlockTypeRegistry($this->blockTypeFactory);
        $blockOptionsResolver    = new BlockOptionsResolver($blockTypeRegistry);
        $this->layoutManipulator = new DeferredLayoutManipulator($this->layoutDataBuilder);
        $this->layoutViewFactory = new LayoutViewFactory(
            $blockTypeRegistry,
            $blockOptionsResolver,
            $this->layoutManipulator
        );
    }

    /**
     * @param string|null $rootId
     *
     * @return BlockView
     */
    protected function getLayoutView($rootId = null)
    {
        $this->layoutManipulator->applyChanges();
        $layoutData = $this->layoutDataBuilder->getLayoutData();

        return $this->layoutViewFactory->createView($layoutData, $this->context, $rootId);
    }
}
