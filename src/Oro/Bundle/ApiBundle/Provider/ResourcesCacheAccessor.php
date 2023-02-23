<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Component\Config\Cache\ConfigCacheStateInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Helper class to load/write entries from/to API resources cache.
 */
class ResourcesCacheAccessor
{
    private CacheItemPoolInterface $cache;
    private ?ConfigCacheStateRegistry $configCacheStateRegistry = null;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function setConfigCacheStateRegistry(ConfigCacheStateRegistry $configCacheStateRegistry): void
    {
        $this->configCacheStateRegistry = $configCacheStateRegistry;
    }

    /**
     * Deletes all entries from the cache.
     */
    public function clear(): void
    {
        $this->cache->clear();
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     * @param string      $id          The ID of the cache entry
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given ID.
     */
    public function fetch(string $version, RequestType $requestType, string $id): mixed
    {
        $cacheKey = $this->getCacheKey($version, $requestType, $id);
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            [$timestamp, $value] = $cacheItem->get();
            if (null === $this->configCacheStateRegistry
                || $this->getConfigCacheState($requestType)->isCacheFresh($timestamp)
            ) {
                return $value;
            }
        }
        return false;
    }

    /**
     * Puts data into the cache.
     * If a cache entry with the given ID already exists, its data will be replaced.
     *
     * @param string      $version     The API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     * @param string      $id          The ID of the cache entry
     * @param mixed       $data        The data to be saved
     */
    public function save(string $version, RequestType $requestType, string $id, mixed $data): void
    {
        $timestamp = null === $this->configCacheStateRegistry
            ? null
            : $this->getConfigCacheState($requestType)->getCacheTimestamp();
        $cacheKey = $this->getCacheKey($version, $requestType, $id);
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set([$timestamp, $data]);
        $this->cache->save($cacheItem);
    }

    private function getCacheKey(string $version, RequestType $requestType, string $id): string
    {
        $cacheKey = $id . $version . (string)$requestType;
        return UniversalCacheKeyGenerator::normalizeCacheKey($cacheKey);
    }

    private function getConfigCacheState(RequestType $requestType): ConfigCacheStateInterface
    {
        return $this->configCacheStateRegistry->getConfigCacheState($requestType);
    }
}
