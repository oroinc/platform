<?php

namespace Oro\Bundle\CacheBundle\Manager;

use Doctrine\Common\Cache\ClearableCache;
use Oro\Bundle\CacheBundle\Provider\SyncCacheInterface;

class OroDataCacheManager
{
    /**
     * @var array
     */
    protected $cacheProviders = [];

    /**
     * Registers a cache provider in this manager
     *
     * @param object $cacheProvider
     */
    public function registerCacheProvider($cacheProvider)
    {
        $this->cacheProviders[] = $cacheProvider;
    }

    /**
     * Makes sure all cache providers are synchronized
     * Call this method in main process if you need to get data modified in a child process
     */
    public function sync()
    {
        foreach ($this->cacheProviders as $cacheProvider) {
            if ($cacheProvider instanceof SyncCacheInterface) {
                $cacheProvider->sync();
            }
        }
    }

    /**
     * Clear cache at all registered cache providers
     */
    public function clear()
    {
        foreach ($this->cacheProviders as $cacheProvider) {
            if ($cacheProvider instanceof ClearableCache) {
                $cacheProvider->deleteAll();
            }
        }
    }
}
