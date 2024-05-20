<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;

/**
 * The event listener that is used to clear ApiDoc cache.
 */
class ApiSourceListener
{
    use OpenApiSourceListenerTrait;

    private CacheManager $cacheManager;

    /**
     * @param CacheManager $cacheManager
     * @param string[]     $excludedFeatures
     */
    public function __construct(CacheManager $cacheManager, array $excludedFeatures)
    {
        $this->cacheManager = $cacheManager;
        $this->excludedFeatures = $excludedFeatures;
    }

    public function clearCache(): void
    {
        $this->cacheManager->clearCaches();
        $this->cacheManager->clearApiDocCache();
    }

    public function onFeaturesChange(FeaturesChange $event): void
    {
        if ($this->isApplicableFeaturesChanged($event)) {
            $this->clearCache();
        }
    }

    public function onEntityConfigPostFlush(PostFlushConfigEvent $event): void
    {
        if ($this->isApplicableEntityConfigsChanged($event)) {
            $this->clearCache();
        }
    }
}
