<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;

/**
 * The event listener that is used to clear the entity structures cache.
 */
class ClearEntityStructureCacheListener
{
    /** @var CacheProvider */
    private $cache;

    /**
     * @param CacheProvider $cache
     */
    public function __construct(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    public function clearCache()
    {
        $this->cache->deleteAll();
    }
}
