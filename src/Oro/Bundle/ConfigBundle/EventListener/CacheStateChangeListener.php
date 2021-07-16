<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;

/**
 * Saves the date when the config cache was changed.
 */
class CacheStateChangeListener
{
    /** @var CacheState */
    protected $cacheState;

    public function __construct(CacheState $cacheState)
    {
        $this->cacheState = $cacheState;
    }

    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        $this->cacheState->renewChangeDate();
    }
}
