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

    public function __construct(CacheState $cacheState)
    {
        $this->cacheState = $cacheState;
    }

    public function onPostFlushConfig(PostFlushConfigEvent $event)
    {
        $this->cacheState->renewChangeDate();
    }
}
