<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\DeferredLayoutManipulatorInterface;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutFactoryInterface;
use Oro\Component\Layout\RawLayoutBuilderInterface;

/**
 * Overrides(decorates) base component's LayoutFactory to override
 * base component's LayoutBuilder with CacheLayoutBuilder.
 */
class CacheLayoutFactory implements LayoutFactoryInterface
{
    /**
     * @var LayoutFactoryInterface
     */
    private $baseLayoutFactory;

    /**
     * @var ExpressionProcessor
     */
    private $expressionProcessor;

    /**
     * @var BlockViewCache|null
     */
    private $blockViewCache;

    /**
     * @var RenderCache
     */
    private $renderCache;

    public function __construct(
        LayoutFactoryInterface $baseLayoutFactory,
        ExpressionProcessor $expressionProcessor,
        RenderCache $renderCache,
        BlockViewCache $blockViewCache = null
    ) {
        $this->baseLayoutFactory = $baseLayoutFactory;
        $this->expressionProcessor = $expressionProcessor;
        $this->blockViewCache = $blockViewCache;
        $this->renderCache = $renderCache;
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
        return new CacheLayoutBuilder(
            $this->getRegistry(),
            $rawLayoutBuilder,
            $layoutManipulator,
            $this->createBlockFactory($layoutManipulator),
            $this->getRendererRegistry(),
            $this->expressionProcessor,
            $this->renderCache,
            $this->blockViewCache
        );
    }
}
