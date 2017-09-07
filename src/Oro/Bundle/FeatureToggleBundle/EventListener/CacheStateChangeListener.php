<?php

namespace Oro\Bundle\FeatureToggleBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Event\FeaturesChange;
use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;

/**
 * Saves the date when the feature toggle cache was changed.
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
     * @param FeaturesChange $event
     */
    public function onFeaturesChange(FeaturesChange $event)
    {
        $this->cacheState->renewChangeDate();
    }
}
