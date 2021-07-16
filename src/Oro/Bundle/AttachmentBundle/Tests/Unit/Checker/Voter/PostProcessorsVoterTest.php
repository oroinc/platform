<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Checker\Voter\PostProcessorsVoter;
use Oro\Bundle\AttachmentBundle\Tests\Unit\CheckProcessorsTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * The test checks the "feature voter", the results of which depend from external libraries: pngquant and jpegoptim.
 */
class PostProcessorsVoterTest extends \PHPUnit\Framework\TestCase
{
    use CheckProcessorsTrait;

    protected function setUp(): void
    {
        $this->checkProcessors();
    }

    public function testVoteWithAnyFeature(): void
    {
        $postProcessorVoter = new PostProcessorsVoter(null, null);

        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $postProcessorVoter->vote('feature'));
    }

    public function testVote(): void
    {
        $postProcessorVoter = new PostProcessorsVoter(null, null);
        $vote = $postProcessorVoter->vote(PostProcessorsVoter::ATTACHMENT_POST_PROCESSORS);

        $this->assertEquals(VoterInterface::FEATURE_ENABLED, $vote);
    }
}
