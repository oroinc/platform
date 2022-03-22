<?php

namespace Oro\Bundle\CacheBundle\Manager;

use Oro\Bundle\CacheBundle\Provider\SyncCacheInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Cache manager service to synchronize all cache providers which implement SyncCacheInterface
 */
class OroDataCacheManager
{
    private array $cacheProviders = [];

    public function registerCacheProvider(AdapterInterface|SyncCacheInterface $cacheProvider): void
    {
        $this->cacheProviders[] = $cacheProvider;
    }

    /**
     * Makes sure all cache providers are synchronized
     * Call this method in main process if you need to get data modified in a child process
     */
    public function sync(): void
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
    public function clear(): void
    {
        foreach ($this->cacheProviders as $cacheProvider) {
            if ($cacheProvider instanceof AdapterInterface) {
                $cacheProvider->clear();
            }
        }
    }
}
