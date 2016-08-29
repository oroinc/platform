<?php

namespace Oro\Bundle\FeatureToggleBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FeatureChange extends Event
{
    const NAME = 'oro_featuretoggle.feature.change';
}
