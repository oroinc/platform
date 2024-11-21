<?php

namespace Oro\Bundle\EntityExtendBundle\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Disable or enable custom_entity_creation feature based on the app`s env.
 */
class CustomEntityCreationFeatureVoter implements VoterInterface
{
    public const string CUSTOM_ENTITY_CREATION_FEATURE = 'custom_entity_creation';

    public function __construct(private bool $kernelDebug)
    {
    }

    #[\Override]
    public function vote($feature, $scopeIdentifier = null): int
    {
        if (self::CUSTOM_ENTITY_CREATION_FEATURE !== $feature) {
            return self::FEATURE_ABSTAIN;
        }

        return $this->kernelDebug ? self::FEATURE_ENABLED : self::FEATURE_DISABLED;
    }
}
