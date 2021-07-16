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

    public function __construct(CacheProvider $cacheProvider)
    {
        $this->arrayCache = $cacheProvider;
    }

    public function onScopeIdChange(ConfigManagerScopeIdUpdateEvent $event): void
    {
        $this->arrayCache->flushAll();
    }
}
