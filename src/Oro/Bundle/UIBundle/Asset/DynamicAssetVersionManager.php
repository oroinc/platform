<?php

namespace Oro\Bundle\UIBundle\Asset;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Provides routines to work with a version of asset packages that can be changed at runtime.
 */
class DynamicAssetVersionManager
{
    protected CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Gets the current version of a given asset package.
     */
    public function getAssetVersion(string $packageName): string
    {
        $versionNumber = $this->getCachedAssetVersion($packageName);

        return 0 !== $versionNumber
            ? (string)$versionNumber
            : '';
    }

    /**
     * Increase the number of the current version of a given asset package.
     */
    public function updateAssetVersion(string $packageName): void
    {
        $versionNumber = $this->getCachedAssetVersion($packageName) + 1;
        $cacheItem = $this->cache->getItem($packageName);
        $this->cache->save($cacheItem->set($versionNumber));
    }

    protected function getCachedAssetVersion(string $packageName): int
    {
        $cacheItem = $this->cache->getItem($packageName);
        return $cacheItem->isHit() ? $cacheItem->get() : 0;
    }
}
