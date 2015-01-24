<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutViewFactory;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class DeferredLayoutManipulatorTestCase extends LayoutBuilderTestCase
{
    /** @var BlockTypeFactoryStub */
    protected $blockTypeFactory;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    protected function setUp()
    {
        $this->blockTypeFactory  = new BlockTypeFactoryStub();
        $blockTypeRegistry       = new BlockTypeRegistry($this->blockTypeFactory);
        $blockOptionsResolver    = new BlockOptionsResolver($blockTypeRegistry);
        $layoutViewFactory       = new LayoutViewFactory($blockTypeRegistry, $blockOptionsResolver);
        $this->layoutBuilder     = new LayoutBuilder($layoutViewFactory);
        $this->layoutManipulator = new DeferredLayoutManipulator($this->layoutBuilder);
    }
}
