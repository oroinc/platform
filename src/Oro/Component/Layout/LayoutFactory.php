<?php

namespace Oro\Component\Layout;

class LayoutFactory implements LayoutFactoryInterface
{
    /** @var ExtensionManagerInterface */
    protected $extensionManager;

    /** @var LayoutRendererRegistryInterface */
    protected $rendererRegistry;

    /**
     * @param ExtensionManagerInterface       $extensionManager
     * @param LayoutRendererRegistryInterface $rendererRegistry
     */
    public function __construct(
        ExtensionManagerInterface $extensionManager,
        LayoutRendererRegistryInterface $rendererRegistry
    ) {
        $this->extensionManager = $extensionManager;
        $this->rendererRegistry = $rendererRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionManager()
    {
        return $this->extensionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getRendererRegistry()
    {
        return $this->rendererRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType($name)
    {
        return $this->extensionManager->getBlockType($name);
    }

    /**
     * {@inheritdoc}
     */
    public function createRawLayoutBuilder()
    {
        return new RawLayoutBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function createLayoutManipulator(RawLayoutBuilderInterface $rawLayoutBuilder)
    {
        return new DeferredLayoutManipulator($rawLayoutBuilder, $this->extensionManager);
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockFactory(DeferredLayoutManipulatorInterface $layoutManipulator)
    {
        return new BlockFactory($this->extensionManager, $layoutManipulator);
    }

    /**
     * {@inheritdoc}
     */
    public function createLayoutBuilder()
    {
        $rawLayoutBuilder  = $this->createRawLayoutBuilder();
        $layoutManipulator = $this->createLayoutManipulator($rawLayoutBuilder);
        $blockFactory      = $this->createBlockFactory($layoutManipulator);

        return new LayoutBuilder(
            $rawLayoutBuilder,
            $layoutManipulator,
            $blockFactory,
            $this->rendererRegistry
        );
    }
}
