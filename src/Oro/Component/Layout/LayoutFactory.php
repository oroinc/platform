<?php

namespace Oro\Component\Layout;

use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;

/**
 * Interface for factory that creates layout registry, builders and manipulators.
 */
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

    #[\Override]
    public function getRegistry()
    {
        return $this->registry;
    }

    #[\Override]
    public function getRendererRegistry()
    {
        return $this->rendererRegistry;
    }

    #[\Override]
    public function getType($name)
    {
        return $this->registry->getType($name);
    }

    #[\Override]
    public function createRawLayoutBuilder()
    {
        return new RawLayoutBuilder();
    }

    #[\Override]
    public function createLayoutManipulator(RawLayoutBuilderInterface $rawLayoutBuilder)
    {
        return new DeferredLayoutManipulator($this->registry, $rawLayoutBuilder);
    }

    #[\Override]
    public function createBlockFactory(DeferredLayoutManipulatorInterface $layoutManipulator)
    {
        return new BlockFactory($this->registry, $layoutManipulator, $this->expressionProcessor);
    }

    #[\Override]
    public function createLayoutBuilder()
    {
        $rawLayoutBuilder = $this->createRawLayoutBuilder();
        $layoutManipulator = $this->createLayoutManipulator($rawLayoutBuilder);
        $blockFactory = $this->createBlockFactory($layoutManipulator);
        $layoutContextStack = new LayoutContextStack();

        return new LayoutBuilder(
            $this->registry,
            $rawLayoutBuilder,
            $layoutManipulator,
            $blockFactory,
            $this->rendererRegistry,
            $this->expressionProcessor,
            $layoutContextStack,
            $this->blockViewCache
        );
    }
}
