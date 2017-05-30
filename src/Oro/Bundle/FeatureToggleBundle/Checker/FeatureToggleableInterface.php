<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

interface FeatureToggleableInterface extends FeatureCheckerAwareInterface
{
    /**
     * @param string $feature
     */
    public function addFeature($feature);

    /**
     * @param null|int|object $scopeIdentifier
     * @return bool
     */
    public function isFeaturesEnabled($scopeIdentifier = null);
}
