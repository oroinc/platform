<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutViewFactory;

class BaseBlockTypeTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var BlockTypeFactoryStub */
    protected $factory;

    /** @var LayoutContext */
    protected $context;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    /** @var BlockOptionsResolver */
    protected $blockOptionsResolver;

    /** @var DeferredLayoutManipulator */
    protected $layoutManipulator;

    /** @var LayoutViewFactory */
    protected $layoutViewFactory;

    protected function setUp()
    {
        $this->context              = new LayoutContext();
        $this->layoutBuilder        = new LayoutBuilder();
        $this->factory              = new BlockTypeFactoryStub();
        $blockTypeRegistry          = new BlockTypeRegistry($this->factory);
        $this->blockOptionsResolver = new BlockOptionsResolver($blockTypeRegistry);
        $this->layoutManipulator    = new DeferredLayoutManipulator($this->layoutBuilder);
        $this->layoutViewFactory    = new LayoutViewFactory(
            $blockTypeRegistry,
            $this->blockOptionsResolver,
            $this->layoutManipulator
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
        $this->layoutBuilder->clear();
        $this->layoutManipulator->add($blockType . '_id', null, $blockType, $options);

        $this->layoutManipulator->applyChanges();
        $layoutData = $this->layoutBuilder->getLayout();

        return $this->layoutViewFactory->createView($layoutData, $this->context);
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
        $this->layoutBuilder->clear();

        return new TestBlockBuilder(
            $this->layoutBuilder,
            $this->layoutManipulator,
            $this->layoutViewFactory,
            $this->context,
            $blockType . '_id',
            $blockType,
            $options
        );
    }
}
