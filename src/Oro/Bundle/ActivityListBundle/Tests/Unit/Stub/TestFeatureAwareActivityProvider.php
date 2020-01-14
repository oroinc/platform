<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Stub;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

class TestFeatureAwareActivityProvider extends TestActivityProvider implements FeatureToggleableInterface
{
    /**
     * {@inheritDoc}
     */
    public function setFeatureChecker(FeatureChecker $featureChecker)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function addFeature($feature)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function isFeaturesEnabled($scopeIdentifier = null)
    {
    }
}
