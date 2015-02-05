<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\BlockFactory;
use Oro\Component\Layout\BlockOptionsResolver;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\DeferredLayoutManipulator;
use Oro\Component\Layout\Extension\Core\CoreExtension;
use Oro\Component\Layout\ExtensionInterface;
use Oro\Component\Layout\ExtensionManager;
use Oro\Component\Layout\LayoutBuilder;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutFactory;
use Oro\Component\Layout\LayoutRendererRegistry;
use Oro\Component\Layout\RawLayoutBuilder;

/**
 * The base test case that helps testing block types
 */
abstract class BaseBlockTypeTestCase extends LayoutTestCase
{
    /** @var LayoutContext */
    protected $context;

    /** @var BlockOptionsResolver */
    protected $blockOptionsResolver;

    /** @var RawLayoutBuilder */
    protected $rawLayoutBuilder;

    /** @var ExtensionManager */
    protected $extensionManager;

    /** @var LayoutBuilder */
    protected $layoutBuilder;

    protected function setUp()
    {
        $this->extensionManager = $this->createExtensionManager();
        foreach ($this->getExtensions() as $extension) {
            $this->extensionManager->addExtension($extension);
        };

        $this->context              = new LayoutContext();
        $this->blockOptionsResolver = new BlockOptionsResolver($this->extensionManager);
        $this->rawLayoutBuilder     = new RawLayoutBuilder();
        $layoutManipulator          = new DeferredLayoutManipulator(
            $this->rawLayoutBuilder,
            $this->extensionManager
        );
        $blockFactory               = new BlockFactory(
            $this->extensionManager,
            $layoutManipulator
        );

        $renderer         = $this->getMock('Oro\Component\Layout\LayoutRendererInterface');
        $rendererRegistry = new LayoutRendererRegistry();
        $rendererRegistry->addRenderer('test', $renderer);
        $rendererRegistry->setDefaultRenderer('test');

        $layoutFactory       = new LayoutFactory($rendererRegistry);
        $this->layoutBuilder = new LayoutBuilder(
            $this->rawLayoutBuilder,
            $layoutManipulator,
            $blockFactory,
            $layoutFactory
        );
    }

    /**
     * @return ExtensionManager
     */
    protected function createExtensionManager()
    {
        return new ExtensionManager();
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
        return $this->extensionManager->getBlockType($blockType);
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
        return $this->blockOptionsResolver->resolveOptions($blockType, $options);
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
