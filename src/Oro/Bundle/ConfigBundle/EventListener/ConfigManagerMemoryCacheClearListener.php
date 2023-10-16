<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;

/**
 * Clears memory cache used by {@see \Oro\Bundle\ConfigBundle\Config\ConfigManager} when a config scope is changed.
 */
class ConfigManagerMemoryCacheClearListener
{
    private MemoryCache $memoryCache;

    public function __construct(MemoryCache $memoryCache)
    {
        $this->memoryCache = $memoryCache;
    }

    public function onScopeIdChange(): void
    {
        $this->memoryCache->deleteAll();
    }
}
