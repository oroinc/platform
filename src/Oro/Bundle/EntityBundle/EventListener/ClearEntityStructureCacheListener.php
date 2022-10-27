<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Symfony\Contracts\Cache\CacheInterface;

/**
 * The event listener that is used to clear the entity structures cache.
 */
class ClearEntityStructureCacheListener
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function clearCache()
    {
        $this->cache->clear();
    }
}
