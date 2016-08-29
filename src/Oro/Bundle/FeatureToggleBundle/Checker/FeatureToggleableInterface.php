<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

interface FeatureToggleableInterface
{
    /**
     * @param FeatureChecker $checker
     */
    public function setFeatureChecker(FeatureChecker $checker);

    /**
     * @param string $feature
     */
    public function addFeature($feature);

    /**
     * @return bool
     */
    public function isFeaturesEnabled();
}
