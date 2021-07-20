<?php

namespace Oro\Bundle\FeatureToggleBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FeaturesChange extends Event
{
    const NAME = 'oro_featuretoggle.features.change';

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
