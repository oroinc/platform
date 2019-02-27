<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * Helper class to load/write entries from/to Data API resources cache.
 */
class ResourcesCacheAccessor
{
    /** @var CacheProvider */
    private $cache;

    /** @var ConfigCacheStateRegistry|null */
    private $configCacheStateRegistry;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param ConfigCacheStateRegistry $configCacheStateRegistry
     */
    public function setConfigCacheStateRegistry(ConfigCacheStateRegistry $configCacheStateRegistry): void
    {
        $this->configCacheStateRegistry = $configCacheStateRegistry;
    }

    /**
     * Deletes all entries from the cache.
     */
    public function clear(): void
    {
        $this->cache->deleteAll();
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     * @param string      $id          The ID of the cache entry
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given ID.
     */
    public function fetch(string $version, RequestType $requestType, string $id)
    {
        $data = $this->cache->fetch($this->getCacheKey($version, $requestType, $id));
        if (false !== $data && null !== $this->configCacheStateRegistry) {
            $configCacheState = $this->configCacheStateRegistry->getConfigCacheState($requestType);
            if ($configCacheState->isCacheChangeable()) {
                list($timestamp, $value) = $data;
                if ($configCacheState->isCacheFresh($timestamp)) {
                    $data = $value;
                } else {
                    $data = false;
                }
            }
        }

        return $data;
    }

    /**
     * Puts data into the cache.
     * If a cache entry with the given ID already exists, its data will be replaced.
     *
     * @param string      $version     The Data API version
     * @param RequestType $requestType The request type, for example "rest", "soap", etc.
     * @param string      $id          The ID of the cache entry
     * @param mixed       $data        The data to be saved
     */
    public function save(string $version, RequestType $requestType, string $id, $data): void
    {
        if (null !== $this->configCacheStateRegistry) {
            $configCacheState = $this->configCacheStateRegistry->getConfigCacheState($requestType);
            if ($configCacheState->isCacheChangeable()) {
                $data = [$configCacheState->getCacheTimestamp(), $data];
            }
        }
        $this->cache->save($this->getCacheKey($version, $requestType, $id), $data);
    }

    /**
     * @param string      $version
     * @param RequestType $requestType
     * @param string      $id
     *
     * @return string
     */
    private function getCacheKey(string $version, RequestType $requestType, string $id): string
    {
        return $id . $version . (string)$requestType;
    }
}
