<?php

namespace Oro\Component\Layout;

class LayoutFactory implements LayoutFactoryInterface
{
    /** @var LayoutRegistryInterface */
    protected $registry;

    /** @var LayoutRendererRegistryInterface */
    protected $rendererRegistry;

    /**
     * @param LayoutRegistryInterface         $registry
     * @param LayoutRendererRegistryInterface $rendererRegistry
     */
    public function __construct(
        LayoutRegistryInterface $registry,
        LayoutRendererRegistryInterface $rendererRegistry
    ) {
        $this->registry         = $registry;
        $this->rendererRegistry = $rendererRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegistry()
    {
        return $this->registry;
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
    public function getType($name)
    {
        return $this->registry->getType($name);
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
        return new DeferredLayoutManipulator($this->registry, $rawLayoutBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function createBlockFactory(DeferredLayoutManipulatorInterface $layoutManipulator)
    {
        return new BlockFactory($this->registry, $layoutManipulator);
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
            $this->registry,
            $rawLayoutBuilder,
            $layoutManipulator,
            $blockFactory,
            $this->rendererRegistry
        );
    }
}
