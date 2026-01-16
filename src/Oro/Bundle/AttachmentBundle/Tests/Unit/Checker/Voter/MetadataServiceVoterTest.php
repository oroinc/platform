<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Checker\Voter\MetadataServiceVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class MetadataServiceVoterTest extends \PHPUnit\Framework\TestCase
{
    private MetadataServiceVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new MetadataServiceVoter();
    }

    public function testVoteAbstainForDifferentFeature(): void
    {
        $this->voter->setEnabled(true);

        self::assertSame(
            VoterInterface::FEATURE_ABSTAIN,
            $this->voter->vote('some_other_feature')
        );
    }

    public function testVoteDisabledByDefault(): void
    {
        self::assertSame(
            VoterInterface::FEATURE_DISABLED,
            $this->voter->vote(MetadataServiceVoter::ATTACHMENT_METADATA_SERVICE)
        );
    }

    public function testVoteDisabledWhenSetToFalse(): void
    {
        $this->voter->setEnabled(false);

        self::assertSame(
            VoterInterface::FEATURE_DISABLED,
            $this->voter->vote(MetadataServiceVoter::ATTACHMENT_METADATA_SERVICE)
        );
    }

    public function testVoteEnabledWhenSetToTrue(): void
    {
        $this->voter->setEnabled(true);

        self::assertSame(
            VoterInterface::FEATURE_ENABLED,
            $this->voter->vote(MetadataServiceVoter::ATTACHMENT_METADATA_SERVICE)
        );
    }
}
