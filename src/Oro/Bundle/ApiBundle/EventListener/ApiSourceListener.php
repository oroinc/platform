<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use Oro\Bundle\ApiBundle\Provider\CacheManager;

/**
 * The event listener that can be used to clear ApiDoc cache.
 */
class ApiSourceListener
{
    /** @var CacheManager */
    private $cacheManager;

    /**
     * @param CacheManager  $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    public function clearCache()
    {
        // clear all api caches data
        $this->cacheManager->clearCaches();
        // clear the cache for API documentation
        $this->cacheManager->clearApiDocCache();
    }
}
