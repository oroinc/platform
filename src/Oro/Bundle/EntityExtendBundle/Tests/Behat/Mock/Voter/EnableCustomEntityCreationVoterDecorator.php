<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Behat\Mock\Voter;

use Oro\Bundle\EntityExtendBundle\Voter\CustomEntityCreationFeatureVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Enable custom_entity_creation feature for behat-test env.
 */
class EnableCustomEntityCreationVoterDecorator implements VoterInterface
{
    public function __construct(protected VoterInterface $innerVoter)
    {
    }

    #[\Override]
    public function vote($feature, $scopeIdentifier = null): int
    {
        if (CustomEntityCreationFeatureVoter::CUSTOM_ENTITY_CREATION_FEATURE === $feature) {
            return self::FEATURE_ENABLED;
        }

        return $this->innerVoter->vote($feature, $scopeIdentifier);
    }
}
