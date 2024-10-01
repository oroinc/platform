<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Stub;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;

class TestFeatureAwareActivityProvider extends TestActivityProvider implements FeatureToggleableInterface
{
    #[\Override]
    public function setFeatureChecker(FeatureChecker $featureChecker)
    {
    }

    #[\Override]
    public function addFeature($feature)
    {
    }

    #[\Override]
    public function isFeaturesEnabled($scopeIdentifier = null)
    {
    }
}
