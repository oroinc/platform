<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

/**
 * Defines the contract for classes that support feature toggling.
 *
 * Classes implementing this interface can manage a collection of features and check
 * whether all of them are enabled for a given scope. This interface extends
 * FeatureCheckerAwareInterface to ensure that implementing classes can receive a
 * FeatureChecker instance. It is useful for components that need to conditionally
 * enable or disable their functionality based on multiple feature flags.
 */
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
