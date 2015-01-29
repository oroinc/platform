<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\LayoutViewFactory;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class DeferredLayoutManipulatorTestCase extends LayoutTestCase
{
    /** @var BlockTypeFactoryStub */
    protected $blockTypeFactory;

    /** @var LayoutContext */
    protected $context;

    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    /** @var LayoutViewFactory */
    protected $layoutViewFactory;

    protected function setUp()
    {
        $this->context           = new LayoutContext();
        $this->rawLayoutBuilder  = new RawLayoutBuilder();
        $this->blockTypeFactory  = new BlockTypeFactoryStub();
        $blockTypeRegistry       = new BlockTypeRegistry($this->blockTypeFactory);
        $blockOptionsResolver    = new BlockOptionsResolver($blockTypeRegistry);
        $this->layoutManipulator = new DeferredLayoutManipulator($this->rawLayoutBuilder);
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
        $rawLayout = $this->rawLayoutBuilder->getRawLayout();

        return $this->layoutViewFactory->createView($rawLayout, $this->context, $rootId);
    }
}
