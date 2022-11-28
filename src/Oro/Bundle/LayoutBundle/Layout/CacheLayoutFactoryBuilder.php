<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProviderInterface;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutContextStack;
use Oro\Component\Layout\LayoutFactoryBuilder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Overrides base component's LayoutFactoryBuilder to override base component's LayoutFactory
 * and to reset metadata cache before layout factory creation.
 */
class CacheLayoutFactoryBuilder extends LayoutFactoryBuilder
{
    private LayoutContextStack $layoutContextStack;

    private ExpressionProcessor $expressionProcessor;

    private RenderCache $renderCache;

    private CacheMetadataProviderInterface $cacheMetadataProvider;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LayoutContextStack $layoutContextStack,
        ExpressionProcessor $expressionProcessor,
        RenderCache $renderCache,
        CacheMetadataProviderInterface $cacheMetadataProvider,
        EventDispatcherInterface $eventDispatcher,
        BlockViewCache $blockViewCache = null
    ) {
        $this->layoutContextStack = $layoutContextStack;
        $this->expressionProcessor = $expressionProcessor;
        $this->renderCache = $renderCache;
        $this->cacheMetadataProvider = $cacheMetadataProvider;
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($expressionProcessor, $blockViewCache);
    }

    /**
     * {@inheritDoc}
     */
    public function getLayoutFactory()
    {
        $this->cacheMetadataProvider->reset();

        return new CacheLayoutFactory(
            parent::getLayoutFactory(),
            $this->layoutContextStack,
            $this->expressionProcessor,
            $this->renderCache,
            $this->eventDispatcher,
            $this->getBlockViewCache()
        );
    }
}
