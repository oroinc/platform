<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Checker\Voter\PostProcessorsVoter;
use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * The test checks the "feature voter", the results of which depend from external libraries: pngquant and jpegoptim.
 */
class PostProcessorsVoterTest extends TestCase
{
    private ProcessorHelper|MockObject $processorHelper;

    private PostProcessorsVoter $voter;

    protected function setUp(): void
    {
        $this->processorHelper = $this->createMock(ProcessorHelper::class);
        $this->voter = new PostProcessorsVoter($this->processorHelper);
    }

    public function testVoteWithAnyFeature(): void
    {
        self::assertEquals(VoterInterface::FEATURE_ABSTAIN, $this->voter->vote('feature'));
    }

    public function testVoteWithDisabledFeature(): void
    {
        self::assertEquals(
            VoterInterface::FEATURE_DISABLED,
            $this->voter->vote(PostProcessorsVoter::ATTACHMENT_POST_PROCESSORS)
        );
    }

    public function testVote(): void
    {
        $this->voter->setEnabled(true);

        $vote = $this->voter->vote(PostProcessorsVoter::ATTACHMENT_POST_PROCESSORS);

        self::assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }
}
