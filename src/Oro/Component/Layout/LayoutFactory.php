<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;

class LayoutFactory implements LayoutFactoryInterface
{
    /** @var LayoutRegistryInterface */
    protected $registry;

    /** @var LayoutRendererRegistryInterface */
    protected $rendererRegistry;

    /** @var ExpressionProcessor */
    protected $expressionProcessor;

    /** @var BlockViewCache|null */
    private $blockViewCache;

    /**
     * @param LayoutRegistryInterface         $registry
     * @param LayoutRendererRegistryInterface $rendererRegistry
     * @param ExpressionProcessor             $expressionProcessor
     * @param BlockViewCache|null             $blockViewCache
     */
    public function __construct(
        LayoutRegistryInterface $registry,
        LayoutRendererRegistryInterface $rendererRegistry,
        ExpressionProcessor $expressionProcessor,
        BlockViewCache $blockViewCache = null
    ) {
        $this->registry            = $registry;
        $this->rendererRegistry    = $rendererRegistry;
        $this->expressionProcessor = $expressionProcessor;
        $this->blockViewCache      = $blockViewCache;
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
        return new BlockFactory($this->registry, $layoutManipulator, $this->expressionProcessor);
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
            $this->rendererRegistry,
            $this->expressionProcessor,
            $this->blockViewCache
        );
    }
}
