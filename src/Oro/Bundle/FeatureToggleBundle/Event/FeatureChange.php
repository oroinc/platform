<?php

namespace Oro\Bundle\FeatureToggleBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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
