<?php

namespace Oro\Bundle\WorkflowBundle\Cache;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms event triggers caches.
 */
class EventTriggerCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var EventTriggerCache[]
     */
    private $eventTriggerCaches = [];

    public function addEventTriggerCache(EventTriggerCache $eventTriggerCache): void
    {
        $this->eventTriggerCaches[] = $eventTriggerCache;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        foreach ($this->eventTriggerCaches as $eventTriggerCache) {
            $eventTriggerCache->build();
        }
    }

    /**
     * {inheritdoc}
     */
    public function isOptional()
    {
        return true;
    }
}
