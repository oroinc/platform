<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Checker\Voter;

use Oro\Bundle\AttachmentBundle\Checker\Voter\PostProcessingVoter;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class PostProcessingVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var PostProcessingVoter */
    private $postProcessingVoter;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->postProcessingVoter = new PostProcessingVoter($this->configManager);
    }

    public function testVoteWithAnyFeature(): void
    {
        $this->assertEquals(VoterInterface::FEATURE_ABSTAIN, $this->postProcessingVoter->vote('feature'));
    }

    /**
     * @dataProvider qualityProvider
     */
    public function testVote(int $jpegQuality, int $pngQuality, int $expected): void
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($pngQuality, $jpegQuality);

        $vote = $this->postProcessingVoter->vote(PostProcessingVoter::ATTACHMENT_POST_PROCESSING);

        $this->assertEquals($expected, $vote);
    }

    public function qualityProvider(): array
    {
        return [
            'Default quality' => [
                'jpeg_quality' => Configuration::JPEG_QUALITY ,
                'png_quality' => Configuration::PNG_QUALITY,
                'expected' => VoterInterface::FEATURE_DISABLED
            ],
            'Changed jpeg and png quality' => [
                'jpeg_quality' => 50,
                'png_quality' => 50,
                'expected' => VoterInterface::FEATURE_ENABLED
            ],
            'Changed jpeg quality' => [
                'jpeg_quality' => 50,
                'png_quality' => Configuration::PNG_QUALITY,
                'expected' => VoterInterface::FEATURE_ENABLED
            ],
            'Changed png quality' => [
                'jpeg_quality' => Configuration::JPEG_QUALITY ,
                'png_quality' => 50,
                'expected' => VoterInterface::FEATURE_ENABLED
            ]
        ];
    }
}
