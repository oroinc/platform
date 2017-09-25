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

    /**
     * @param CacheState $cacheState
     */
    public function __construct(CacheState $cacheState)
    {
        $this->cacheState = $cacheState;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigUpdate(ConfigUpdateEvent $event)
    {
        $this->cacheState->renewChangeDate();
    }
}
