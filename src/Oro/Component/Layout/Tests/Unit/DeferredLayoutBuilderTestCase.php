<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\DeferredLayoutBuilder;
use Oro\Component\Layout\Tests\Unit\Fixtures\BlockTypeFactoryStub;

class DeferredLayoutBuilderTestCase extends LayoutBuilderTestCase
{
    /** @var BlockTypeFactoryStub */
    protected $blockTypeFactory;

    /** @var DeferredLayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $this->blockTypeFactory = new BlockTypeFactoryStub();
        $blockTypeRegistry      = new BlockTypeRegistry($this->blockTypeFactory);
        $blockOptionsResolver   = new BlockOptionsResolver($blockTypeRegistry);

        $this->layoutBuilder = new DeferredLayoutBuilder(
            new LayoutBuilder(
                $blockTypeRegistry,
                $blockOptionsResolver
            )
        );
    }
}
