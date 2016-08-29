<?php

namespace Oro\Bundle\FeatureToggleBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FeaturesChange extends Event
{
    const NAME = 'oro_featuretoggle.features.change';
}
