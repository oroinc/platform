<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * The memory cache provider which automatically generates key from given arguments.
 */
class MemoryCacheProvider implements MemoryCacheProviderInterface
{
    /** @var UniversalCacheKeyGenerator */
    private $universalCacheKeyGenerator;

    /** @var ArrayAdapter */
    private $cache;

    public function __construct(UniversalCacheKeyGenerator $universalCacheKeyGenerator, ArrayAdapter $cache)
    {
        $this->universalCacheKeyGenerator = $universalCacheKeyGenerator;
        $this->cache = $cache;
    }

    /**
     * Retrieves cached data by $cacheKeyArguments.
     * Gets value from callback and stores it if no cached data found.
     *
     * @param mixed $cacheKeyArguments The parts of key of the item to retrieve from the cache
     * @param callable|null $callback Should return the computed value for the given key/item
     *
     * @return mixed
     */
    public function get($cacheKeyArguments, ?callable $callback = null)
    {
        $cacheKey = $this->getCacheKey($cacheKeyArguments);

        if (!$callback) {
            $cacheItem = $this->cache->getItem($cacheKey);

            return $cacheItem->isHit() ? $cacheItem->get() : null;
        }

        return $this->cache->get($cacheKey, $callback);
    }

    /**
     * @param array $cacheKeyArguments
     *
     * @return string
     */
    private function getCacheKey($cacheKeyArguments): string
    {
        return $this->universalCacheKeyGenerator->generate($cacheKeyArguments);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
        $this->cache->reset();
    }
}
