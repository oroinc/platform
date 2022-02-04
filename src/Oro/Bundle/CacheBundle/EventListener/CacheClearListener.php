<?php

namespace Oro\Bundle\CacheBundle\EventListener;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * Clears the cache when a specific event occurs.
 */
class CacheClearListener
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }
}
