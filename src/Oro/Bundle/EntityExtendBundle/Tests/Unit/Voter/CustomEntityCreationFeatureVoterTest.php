<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Voter;

use Oro\Bundle\EntityExtendBundle\Voter\CustomEntityCreationFeatureVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use PHPUnit\Framework\TestCase;

class CustomEntityCreationFeatureVoterTest extends TestCase
{
    public function testVoteFeatureEnabledWhenKernelDebugIsTrue(): void
    {
        $voter = new CustomEntityCreationFeatureVoter(true);
        $result = $voter->vote('custom_entity_creation');

        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $result);
    }

    public function testVoteFeatureDisabledWhenKernelDebugIsFalse(): void
    {
        $voter = new CustomEntityCreationFeatureVoter(false);
        $result = $voter->vote('custom_entity_creation');

        $this->assertEquals(VoterInterface::FEATURE_DISABLED, $result);
    }

    public function testVoteFeatureAbstainForDifferentFeature(): void
    {
        $voter = new CustomEntityCreationFeatureVoter(true);
        $result = $voter->vote('some_other_feature');

        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $result);
    }
}
