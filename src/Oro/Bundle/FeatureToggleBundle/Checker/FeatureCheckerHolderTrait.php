<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

/**
 * Provides feature checker storage and feature management functionality.
 *
 * This trait can be used by classes that need to store a {@see FeatureChecker} instance and
 * manage a list of features to check. It provides methods to set the feature checker,
 * add features to the list, and check if all registered features are enabled for a
 * given scope. This is useful for classes that need to verify multiple feature flags
 * before proceeding with certain operations.
 */
trait FeatureCheckerHolderTrait
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var array
     */
    protected $features = [];

    public function setFeatureChecker(FeatureChecker $checker)
    {
        $this->featureChecker = $checker;
    }

    /**
     * @param string $feature
     */
    public function addFeature($feature)
    {
        $this->features[] = $feature;
    }

    /**
     * @param null|int|object $scopeIdentifier
     * @return bool
     */
    public function isFeaturesEnabled($scopeIdentifier = null)
    {
        foreach ($this->features as $feature) {
            if (!$this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier)) {
                return false;
            }
        }

        return true;
    }
}
