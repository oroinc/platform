<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Event\ConfigManagerScopeIdUpdateEvent;

/**
 * Saves the date when the config cache was changed.
 */
class ConfigManagerArrayCacheClearListener
{
    /** @var CacheProvider */
    private $arrayCache;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->arrayCache = $cacheProvider;
    }

    /**
     * @param ConfigManagerScopeIdUpdateEvent $event
     */
    public function onScopeIdChange(ConfigManagerScopeIdUpdateEvent $event): void
    {
        $this->arrayCache->flushAll();
    }
}
