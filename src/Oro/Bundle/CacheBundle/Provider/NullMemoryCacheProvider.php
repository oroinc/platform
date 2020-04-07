<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Skips any caching. The goal is to be used as a default substitute for MemoryCacheProvider.
 */
class NullMemoryCacheProvider implements MemoryCacheProviderInterface
{
    /**
     * {@inheritdoc}
     *
     * Calls given callback and returns value without caching.
     */
    public function get($cacheKeyArguments, ?callable $callback = null)
    {
        if (is_callable($callback)) {
            return $callback();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): void
    {
    }
}
