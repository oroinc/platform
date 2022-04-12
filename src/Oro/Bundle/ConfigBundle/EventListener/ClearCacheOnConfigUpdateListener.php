<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Clears caches after $configParameter in system config is updated.
 */
class ClearCacheOnConfigUpdateListener
{
    /** @var CacheItemPoolInterface[] */
    private array $cachesToClear = [];

    private string $configParameter;

    public function __construct(string $configParameter)
    {
        $this->configParameter = $configParameter;
    }

    public function addCacheToClear(CacheItemPoolInterface $cache): void
    {
        $this->cachesToClear[] = $cache;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event): void
    {
        if ($event->isChanged($this->configParameter)) {
            foreach ($this->cachesToClear as $clearableCache) {
                $clearableCache->clear();
            }
        }
    }
}
