<?php

namespace Oro\Bundle\FeatureToggleBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when multiple features' enablement statuses change.
 *
 * This event is triggered when a batch of features are enabled or disabled together,
 * allowing listeners to react to multiple feature state changes in a single event.
 * It carries a change set containing the names and new enablement statuses of all
 * affected features. This is more efficient than dispatching individual {@see FeatureChange}
 * events when multiple features change simultaneously.
 */
class FeaturesChange extends Event
{
    public const NAME = 'oro_featuretoggle.features.change';

    /**
     * @var array
     */
    protected $changeSet = [];

    public function __construct(array $changeSet)
    {
        $this->changeSet = $changeSet;
    }

    /**
     * @return array
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }
}
