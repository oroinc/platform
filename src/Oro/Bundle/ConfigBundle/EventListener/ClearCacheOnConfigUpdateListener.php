<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Doctrine\Common\Cache\ClearableCache;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;

/**
 * Clears caches after $configParameter in system config is updated.
 */
class ClearCacheOnConfigUpdateListener
{
    /** @var ClearableCache[] */
    private array $cachesToClear = [];

    private string $configParameter;

    public function __construct(string $configParameter)
    {
        $this->configParameter = $configParameter;
    }

    public function addCacheToClear(ClearableCache $cache): void
    {
        $this->cachesToClear[] = $cache;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event): void
    {
        if ($event->isChanged($this->configParameter)) {
            foreach ($this->cachesToClear as $clearableCache) {
                $clearableCache->deleteAll();
            }
        }
    }
}
