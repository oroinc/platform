<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;

/**
 * Saves the date when the entity config cache was changed.
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
     * @param PostFlushConfigEvent $event
     */
    public function onPostFlushConfig(PostFlushConfigEvent $event)
    {
        $this->cacheState->renewChangeDate();
    }
}
