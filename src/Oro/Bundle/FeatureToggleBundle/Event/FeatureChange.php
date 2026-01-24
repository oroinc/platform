<?php

namespace Oro\Bundle\FeatureToggleBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a single feature's enablement status changes.
 *
 * This event is triggered whenever a feature is enabled or disabled, allowing listeners
 * to react to feature state changes. It carries the name of the feature that changed and
 * its new enablement status. Listeners can use this event to invalidate caches, update
 * UI state, or perform other necessary actions when feature toggles change.
 */
class FeatureChange extends Event
{
    const NAME = 'oro_featuretoggle.feature.change';

    /**
     * @var string
     */
    protected $featureName;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @param string $featureName
     * @param bool $enabled
     */
    public function __construct($featureName, $enabled)
    {
        $this->featureName = $featureName;
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getFeatureName()
    {
        return $this->featureName;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
}
