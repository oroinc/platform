<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

/**
 * Clears event triggers caches.
 */
class EventTriggerCacheClearer implements CacheClearerInterface
{
    /**
     * @var EventTriggerCache[]
     */
    private $eventTriggerCaches = [];

    public function addEventTriggerCache(EventTriggerCache $eventTriggerCache): void
    {
        $this->eventTriggerCaches[] = $eventTriggerCache;
    }

    #[\Override]
    public function clear($cacheDir): void
    {
        foreach ($this->eventTriggerCaches as $eventTriggerCache) {
            $eventTriggerCache->invalidateCache();
        }
    }
}
