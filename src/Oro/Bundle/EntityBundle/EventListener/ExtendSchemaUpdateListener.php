<?php

namespace Oro\Bundle\EntityBundle\EventListener;

use Doctrine\Common\Cache\Cache;

use Oro\Bundle\EntityExtendBundle\Event\ExtendSchemaUpdateEvent;

/**
 * Delete custom entities menu cache on schema update
 * new entities may be added and menu should be updated
 */
class ExtendSchemaUpdateListener
{
    /** @var Cache */
    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param ExtendSchemaUpdateEvent $event
     */
    public function onExtendSchemaUpdate(ExtendSchemaUpdateEvent $event)
    {
        if ($event->isUpdateRouting() && $this->cache->contains(NavigationListener::CACHE_KEY)) {
            $this->cache->delete(NavigationListener::CACHE_KEY);
        }
    }
}
