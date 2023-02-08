<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutContextStack;
use Oro\Component\Layout\LayoutFactoryInterface;
use Oro\Component\Layout\RawLayoutBuilderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Overrides(decorates) base component's LayoutFactory to override
 * base component's LayoutBuilder with CacheLayoutBuilder.
 */
class CacheLayoutFactory implements LayoutFactoryInterface
{
    private LayoutFactoryInterface $baseLayoutFactory;

    private LayoutContextStack $layoutContextStack;

    private ExpressionProcessor $expressionProcessor;

    private RenderCache $renderCache;

    private EventDispatcherInterface $eventDispatcher;

    private ?BlockViewCache $blockViewCache;

    public function __construct(
        LayoutFactoryInterface $baseLayoutFactory,
        LayoutContextStack $layoutContextStack,
        ExpressionProcessor $expressionProcessor,
        RenderCache $renderCache,
        EventDispatcherInterface $eventDispatcher,
        BlockViewCache $blockViewCache = null
    ) {
        $this->baseLayoutFactory = $baseLayoutFactory;
        $this->layoutContextStack = $layoutContextStack;
        $this->expressionProcessor = $expressionProcessor;
        $this->renderCache = $renderCache;
        $this->eventDispatcher = $eventDispatcher;
        $this->blockViewCache = $blockViewCache;
    }

    /**
     * {@inheritDoc}
     */
    public function getRegistry()
    {
        return $this->baseLayoutFactory->getRegistry();
    }

    /**
     * {@inheritDoc}
     */
    public function getRendererRegistry()
    {
        return $this->baseLayoutFactory->getRendererRegistry();
    }

    /**
     * {@inheritDoc}
     */
    public function getType($name)
    {
        return $this->baseLayoutFactory->getType($name);
    }

    /**
     * {@inheritDoc}
     */
    public function createRawLayoutBuilder()
    {
        return $this->baseLayoutFactory->createRawLayoutBuilder();
    }

    /**
     * {@inheritDoc}
     */
    public function createLayoutManipulator(RawLayoutBuilderInterface $rawLayoutBuilder)
    {
        return $this->baseLayoutFactory->createLayoutManipulator($rawLayoutBuilder);
    }

    /**
     * {@inheritDoc}
     */
    public function createBlockFactory(DeferredLayoutManipulatorInterface $layoutManipulator)
    {
        return $this->baseLayoutFactory->createBlockFactory($layoutManipulator);
    }

    /**
     * {@inheritDoc}
     */
    public function createLayoutBuilder()
    {
        $rawLayoutBuilder = $this->createRawLayoutBuilder();
        $layoutManipulator = $this->createLayoutManipulator($rawLayoutBuilder);
        $builder = new CacheLayoutBuilder(
            $this->getRegistry(),
            $rawLayoutBuilder,
            $layoutManipulator,
            $this->createBlockFactory($layoutManipulator),
            $this->getRendererRegistry(),
            $this->expressionProcessor,
            $this->layoutContextStack,
            $this->renderCache,
            $this->blockViewCache
        );
        $builder->setEventDispatcher($this->eventDispatcher);

        return $builder;
    }
}
