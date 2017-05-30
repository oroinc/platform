<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker\Voter;

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
