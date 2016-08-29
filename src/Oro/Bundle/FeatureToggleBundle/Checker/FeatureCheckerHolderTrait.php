<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

trait FeatureCheckerHolderTrait
{
    /**
     * @var FeatureChecker
     */
    protected $checker;

    /**
     * @var array
     */
    protected $features = [];

    /**
     * @param FeatureChecker $checker
     */
    public function setFeatureChecker(FeatureChecker $checker)
    {
        $this->checker = $checker;
    }

    /**
     * @param string $feature
     */
    public function addFeature($feature)
    {
        $this->features[] = $feature;
    }

    /**
     * @return bool
     */
    public function isFeaturesEnabled()
    {
        foreach ($this->features as $feature) {
            if (!$this->checker->isFeatureEnabled($feature)) {
                return false;
            }
        }

        return true;
    }
}
