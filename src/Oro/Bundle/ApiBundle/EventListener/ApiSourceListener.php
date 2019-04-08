<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor\CachingApiDocExtractor;
use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\ApiBundle\Provider\ResourcesCache;

/**
 * The event listener that can be used to clear ApiDoc cache.
 */
class ApiSourceListener
{
    /** @var ResourcesCache */
    private $resourcesCache;

    /** @var ApiDocExtractor */
    private $apiDocExtractor;

    /** @var string[] */
    private $apiDocViews;

    /** @var CacheManager */
    private $cacheManager;

    /**
     * @param ResourcesCache  $resourcesCache
     * @param ApiDocExtractor $apiDocExtractor
     * @param string[]        $apiDocViews
     */
    public function __construct(
        ResourcesCache $resourcesCache,
        ApiDocExtractor $apiDocExtractor,
        array $apiDocViews
    ) {
        $this->resourcesCache = $resourcesCache;
        $this->apiDocExtractor = $apiDocExtractor;
        $this->apiDocViews = $apiDocViews;
    }

    /**
     * @param CacheManager $cacheManager
     */
    public function setCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function clearCache()
    {
        if (!$this->cacheManager) {
            $this->clearCacheWithoutManager();
        }

        // clear all api caches data
        $this->cacheManager->clearCaches();
        // clear the cache for API documentation
        $this->cacheManager->clearApiDocCache();
    }

    /**
     * @deprecated since 3.1, will be removed in 4.0
     */
    private function clearCacheWithoutManager()
    {
        // The old behavior was left to not break BC
        $this->resourcesCache->clear();
        if ($this->apiDocExtractor instanceof CachingApiDocExtractor) {
            foreach ($this->apiDocViews as $view) {
                $this->apiDocExtractor->clear($view);
            }
        }
    }
}
