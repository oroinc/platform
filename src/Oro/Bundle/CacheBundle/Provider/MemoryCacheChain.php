<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Doctrine\Common\Cache\CacheProvider;

/**
 * The cache provider that adds a memory cache provider before the main cache provider
 * in case if the application works not in CLI.
 * This prevent duplicated cache requests and as result improve the performance of cache operations.
 * Also, this providers decreases the number of "contains" operations to the main cache provider,
 * it is possible because the "fetch" operation returns FALSE
 * if the requested entry does not exist in the cache.
 */
class MemoryCacheChain extends CacheProvider
{
    /** @var ArrayCache|null */
    private $memoryCache;

    /** @var CacheProvider|null */
    private $cache;

    /** @var CacheProvider[] */
    private $caches = [];

    /**
     * @param CacheProvider|null $cache
     */
    public function __construct(CacheProvider $cache = null)
    {
        if (PHP_SAPI !== 'cli') {
            $this->memoryCache = new ArrayCache();
            $this->caches[] = $this->memoryCache;
        }
        $this->cache = $cache;
        if (null !== $this->cache) {
            $this->caches[] = $this->cache;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace($namespace)
    {
        parent::setNamespace($namespace);

        foreach ($this->caches as $cache) {
            $cache->setNamespace($namespace);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        if (null !== $this->memoryCache) {
            $value = $this->memoryCache->doFetch($id);
            if (false !== $value || $this->memoryCache->doContains($id)) {
                return $value;
            }
        }

        if (null !== $this->cache) {
            $value = $this->cache->doFetch($id);
            if (false !== $value || $this->cache->doContains($id)) {
                if (null !== $this->memoryCache) {
                    $this->memoryCache->doSave($id, $value);
                }

                return $value;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $keysCount = count($keys);

        if (null !== $this->memoryCache) {
            $fetchedValues = $this->memoryCache->doFetchMultiple($keys);
            if (count($fetchedValues) === $keysCount) {
                return $fetchedValues;
            }
        }

        if (null !== $this->cache) {
            $fetchedValues = $this->cache->doFetchMultiple($keys);
            if (null !== $this->memoryCache && count($fetchedValues) === $keysCount) {
                $this->memoryCache->doSaveMultiple($fetchedValues);
            }

            return $fetchedValues;
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        foreach ($this->caches as $cache) {
            if ($cache->doContains($id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $stored = true;

        foreach ($this->caches as $cache) {
            $stored = $cache->doSave($id, $data, $lifeTime) && $stored;
        }

        return $stored;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0)
    {
        $stored = true;

        foreach ($this->caches as $cache) {
            $stored = $cache->doSaveMultiple($keysAndValues, $lifetime) && $stored;
        }

        return $stored;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $deleted = true;

        foreach ($this->caches as $cache) {
            $deleted = $cache->doDelete($id) && $deleted;
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys)
    {
        $deleted = true;

        foreach ($this->caches as $cache) {
            $deleted = $cache->doDeleteMultiple($keys) && $deleted;
        }

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $flushed = true;

        foreach ($this->caches as $cache) {
            $flushed = $cache->doFlush() && $flushed;
        }

        return $flushed;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        $stats = [];

        foreach ($this->caches as $cache) {
            $stats[] = $cache->doGetStats();
        }

        return $stats;
    }
}
