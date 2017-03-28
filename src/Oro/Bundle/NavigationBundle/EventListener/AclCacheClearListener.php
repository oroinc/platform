<?php

namespace Oro\Bundle\NavigationBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;

class AclCacheClearListener
{
    /** @var CacheProvider */
    protected $menuCache;

    /**
     * @param CacheProvider $menuCache
     */
    public function __construct(CacheProvider $menuCache)
    {
        $this->menuCache = $menuCache;
    }

    /**
     * Clear menu cache.
     */
    public function onCacheClear()
    {
        $this->menuCache->deleteAll();
    }
}
