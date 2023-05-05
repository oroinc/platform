<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Checker\Voter\PostProcessorsVoter;
use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * The test checks the "feature voter", the results of which depend from external libraries: pngquant and jpegoptim.
 */
class PostProcessorsVoterTest extends \PHPUnit\Framework\TestCase
{
    public function testVoteWithAnyFeature(): void
    {
        $processorHelper = $this->createMock(ProcessorHelper::class);
        $processorHelper
            ->method('librariesExists')
            ->willReturn(false);
        $postProcessorVoter = new PostProcessorsVoter($processorHelper);

        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $postProcessorVoter->vote('feature'));
    }

    public function testVote(): void
    {
        $processorHelper = $this->createMock(ProcessorHelper::class);
        $processorHelper
            ->method('librariesExists')
            ->willReturn(true);
        $postProcessorVoter = new PostProcessorsVoter($processorHelper);
        $vote = $postProcessorVoter->vote(PostProcessorsVoter::ATTACHMENT_POST_PROCESSORS);

        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }
}
