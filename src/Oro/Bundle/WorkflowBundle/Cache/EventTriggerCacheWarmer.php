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

    #[\Override]
    public function warmUp($cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->eventTriggerCaches as $eventTriggerCache) {
            $eventTriggerCache->build();
        }
        return [];
    }

    /**
     * {inheritdoc}
     */
    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }
}
