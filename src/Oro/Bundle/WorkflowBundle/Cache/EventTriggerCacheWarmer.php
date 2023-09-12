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
    public function warmUp($cacheDir): array
    {
        foreach ($this->eventTriggerCaches as $eventTriggerCache) {
            $eventTriggerCache->build();
        }
        return [];
    }

    /**
     * {inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }
}
