<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Symfony\Component\Cache\ResettableInterface;

/**
 * Interface for memory cache provider.
 */
interface MemoryCacheProviderInterface extends ResettableInterface
{
    /**
     * @param mixed $cacheKeyArguments The parts of key of the item to retrieve from the cache
     * @param callable|null $callback Should return the computed value for the given key/item
     *
     * @return mixed
     */
    public function get($cacheKeyArguments, ?callable $callback = null);
}
