<?php

namespace Oro\Bundle\CacheBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Clears the cache when a specific event occurs.
 */
class CacheClearListener
{
    /** @var CacheProvider */
    private $cache;

    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    public function clearCache(): void
    {
        $this->cache->deleteAll();
    }
}
