<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockRendererRegistry;
use Oro\Component\Layout\BlockTypeRegistry;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\RawLayoutBuilder;
use Oro\Component\Layout\LayoutFactory;
use Oro\Component\Layout\LayoutViewFactory;

/**
 * The base test case that helps testing block types
 */
abstract class BaseBlockTypeTestCase extends LayoutTestCase
{
    /** @var BlockTypeFactoryStub */
    protected $factory;

    /** @var LayoutContext */
    protected $context;

    /** @var BlockOptionsResolver */
    protected $blockOptionsResolver;

    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $this->context              = new LayoutContext();
        $this->factory              = new BlockTypeFactoryStub();
        $blockTypeRegistry          = new BlockTypeRegistry($this->factory);
        $this->blockOptionsResolver = new BlockOptionsResolver($blockTypeRegistry);
        $this->rawLayoutBuilder     = new RawLayoutBuilder();
        $layoutManipulator          = new DeferredLayoutManipulator($this->rawLayoutBuilder);
        $layoutViewFactory          = new LayoutViewFactory(
            $blockTypeRegistry,
            $this->blockOptionsResolver,
            $layoutManipulator
        );

        $renderer         = $this->getMock('Oro\Component\Layout\BlockRendererInterface');
        $rendererRegistry = new BlockRendererRegistry();
        $rendererRegistry->addRenderer('test', $renderer);
        $rendererRegistry->setDefaultRenderer('test');

        $layoutFactory       = new LayoutFactory($rendererRegistry);
        $this->layoutBuilder = new LayoutBuilder(
            $this->rawLayoutBuilder,
            $layoutManipulator,
            $layoutViewFactory,
            $layoutFactory
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
        $this->rawLayoutBuilder->clear();

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
        $this->rawLayoutBuilder->clear();

        return new TestBlockBuilder(
            $this->layoutBuilder,
            $this->context,
            $blockType . '_id',
            $blockType,
            $options
        );
    }
}
