<?php

namespace Oro\Bundle\CacheBundle\Provider;

/**
 * Skips any caching. The goal is to be used as a default substitute for MemoryCacheProvider.
 */
class NullMemoryCacheProvider implements MemoryCacheProviderInterface
{
    #[\Override]
    public function get($cacheKeyArguments, ?callable $callback = null)
    {
        if (is_callable($callback)) {
            return $callback();
        }

        return null;
    }

    #[\Override]
    public function reset(): void
    {
    }
}
