<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker\Voter;

/**
 * Defines the contract for voting on whether a feature is enabled.
 *
 * Implementations of this interface participate in a voting mechanism to determine
 * the enablement status of features. Each voter can vote to enable, disable, or abstain
 * from voting on a feature. Multiple voters can be consulted to make a final decision
 * about feature enablement, supporting flexible and extensible feature toggle strategies.
 * Voters can optionally consider a scope identifier to provide scope-specific feature
 * enablement decisions.
 */
interface VoterInterface
{
    const FEATURE_ENABLED = 1;
    const FEATURE_ABSTAIN = 0;
    const FEATURE_DISABLED = -1;

    /**
     * @param string $feature
     * @param object|int|null $scopeIdentifier
     * @return int either FEATURE_ENABLED, FEATURE_ABSTAIN, or FEATURE_DISABLED
     */
    public function vote($feature, $scopeIdentifier = null);
}
