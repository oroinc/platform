<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ExtensionInterface;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Component\Layout\LayoutFactoryInterface;
use Oro\Component\Layout\Layouts;

/**
 * The base test case that helps testing block types
 */
abstract class BaseBlockTypeTestCase extends LayoutTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var LayoutFactoryInterface */
    protected $layoutFactory;

    protected function setUp()
    {
        $this->context = new LayoutContext();

        $layoutFactoryBuilder = Layouts::createLayoutFactoryBuilder();
        $this->initializeLayoutFactoryBuilder($layoutFactoryBuilder);
        foreach ($this->getExtensions() as $extension) {
            $layoutFactoryBuilder->addExtension($extension);
        };
        $this->layoutFactory = $layoutFactoryBuilder->getLayoutFactory();
    }

    /**
     * @param LayoutFactoryBuilderInterface $layoutFactoryBuilder
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        $layoutFactoryBuilder->addRenderer(
            'test',
            $this->getMock('Oro\Component\Layout\LayoutRendererInterface')
        );
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getExtensions()
    {
        return [];
    }

    /**
     * Returns a block type by its name
     *
     * @param string $blockType
     *
     * @return BlockTypeInterface
     */
    protected function getBlockType($blockType)
    {
        return $this->layoutFactory->getType($blockType);
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
        $blockOptionsResolver = new BlockOptionsResolver($this->layoutFactory->getExtensionManager());

        return $blockOptionsResolver->resolveOptions($blockType, $options);
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
        $layoutBuilder = $this->layoutFactory->createLayoutBuilder();
        $layoutBuilder->add($blockType . '_id', null, $blockType, $options);
        $layout = $layoutBuilder->getLayout($this->context);

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
        return new TestBlockBuilder(
            $this->layoutFactory->createLayoutBuilder(),
            $this->context,
            $blockType . '_id',
            $blockType,
            $options
        );
    }
}
