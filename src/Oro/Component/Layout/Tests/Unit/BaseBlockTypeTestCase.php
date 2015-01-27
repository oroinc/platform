<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutDataBuilder;
use Oro\Component\Layout\LayoutViewFactory;

class BaseBlockTypeTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var BlockTypeFactoryStub */
    protected $factory;

    /** @var LayoutContext */
    protected $context;

    /** @var BlockOptionsResolver */
    protected $blockOptionsResolver;

    /** @var LayoutDataBuilder */
    protected $layoutDataBuilder;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $this->context              = new LayoutContext();
        $this->factory              = new BlockTypeFactoryStub();
        $blockTypeRegistry          = new BlockTypeRegistry($this->factory);
        $this->blockOptionsResolver = new BlockOptionsResolver($blockTypeRegistry);
        $this->layoutDataBuilder    = new LayoutDataBuilder();
        $layoutManipulator          = new DeferredLayoutManipulator($this->layoutDataBuilder);
        $layoutViewFactory          = new LayoutViewFactory(
            $blockTypeRegistry,
            $this->blockOptionsResolver,
            $layoutManipulator
        );
        $this->layoutBuilder        = new LayoutBuilder(
            $this->layoutDataBuilder,
            $layoutManipulator,
            $layoutViewFactory
        );
    }

    /**
     * Asks the given block type to resolve options
     *
     * @param string $blockType
     * @param array  $options
     *
     * @return array The resolved options
     */
    protected function resolveOptions($blockType, array $options)
    {
        return $this->blockOptionsResolver->resolve($blockType, $options);
    }

    /**
     * Creates a view for the given block type
     *
     * @param string $blockType
     * @param array  $options
     *
     * @return BlockView
     */
    protected function getBlockView($blockType, array $options = [])
    {
        $this->layoutDataBuilder->clear();

        $this->layoutBuilder->add($blockType . '_id', null, $blockType, $options);
        $layout = $this->layoutBuilder->getLayout($this->context);

        return $layout->getView();
    }

    /**
     * Creates a builder which can be used to build block hierarchy
     *
     * @param       $blockType
     * @param array $options
     *
     * @return TestBlockBuilder
     */
    protected function getBlockBuilder($blockType, array $options = [])
    {
        $this->layoutDataBuilder->clear();

        return new TestBlockBuilder(
            $this->layoutBuilder,
            $this->context,
            $blockType . '_id',
            $blockType,
            $options
        );
    }
}
