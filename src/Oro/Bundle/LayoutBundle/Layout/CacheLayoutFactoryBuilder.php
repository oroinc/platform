<?php

namespace Oro\Bundle\LayoutBundle\Layout;

use Oro\Bundle\LayoutBundle\Cache\Metadata\CacheMetadataProvider;
use Oro\Bundle\LayoutBundle\Cache\RenderCache;
use Oro\Component\Layout\BlockViewCache;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\LayoutFactoryBuilder;

/**
 * Overrides base component's LayoutFactoryBuilder to override base component's LayoutFactory
 * and to reset metadata cache before layout factory creation.
 */
class CacheLayoutFactoryBuilder extends LayoutFactoryBuilder
{
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

    /**
     * @var CacheMetadataProvider
     */
    private $cacheMetadataProvider;

    public function __construct(
        ExpressionProcessor $expressionProcessor,
        RenderCache $renderCache,
        CacheMetadataProvider $cacheMetadataProvider,
        BlockViewCache $blockViewCache = null
    ) {
        $this->expressionProcessor = $expressionProcessor;
        $this->renderCache = $renderCache;
        $this->cacheMetadataProvider = $cacheMetadataProvider;
        $this->blockViewCache = $blockViewCache;

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
            $this->expressionProcessor,
            $this->renderCache,
            $this->getBlockViewCache()
        );
    }
}
