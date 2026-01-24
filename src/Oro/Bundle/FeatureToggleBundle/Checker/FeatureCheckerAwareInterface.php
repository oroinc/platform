<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

/**
 * Defines the contract for classes that require feature checker injection.
 *
 * Classes implementing this interface can receive a {@see FeatureChecker} instance to enable
 * feature toggle checking capabilities. This is typically used with dependency injection
 * to provide feature checking functionality to services that need to conditionally
 * enable or disable behavior based on feature flags.
 */
interface FeatureCheckerAwareInterface
{
    /**
     * @param FeatureChecker $featureChecker
     * @return mixed
     */
    public function setFeatureChecker(FeatureChecker $featureChecker);
}
