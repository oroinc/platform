<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Checker\Voter\PostProcessorsVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use PHPUnit\Framework\TestCase;

/**
 * The test checks the "feature voter", the results of which depend from external libraries: pngquant and jpegoptim.
 */
class PostProcessorsVoterTest extends TestCase
{
    public function testVoteWithAnyFeature(): void
    {
        $postProcessorVoter = new PostProcessorsVoter(null, null);

        self::assertEquals(VoterInterface::FEATURE_ABSTAIN, $postProcessorVoter->vote('feature'));
    }

    public function testVoteWithDisabledFeature(): void
    {
        $postProcessorVoter = new PostProcessorsVoter(null, null);

        self::assertEquals(
            VoterInterface::FEATURE_DISABLED,
            $postProcessorVoter->vote(PostProcessorsVoter::ATTACHMENT_POST_PROCESSORS)
        );
    }

    public function testVote(): void
    {
        $postProcessorVoter = new PostProcessorsVoter(null, null);
        $postProcessorVoter->setEnabled(true);

        $vote = $postProcessorVoter->vote(PostProcessorsVoter::ATTACHMENT_POST_PROCESSORS);

        self::assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }
}
