<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Checker\Voter\PostProcessingVoter;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentPostProcessorsProvider;
use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class AttachmentPostProcessorsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentPostProcessorsProvider */
    private $attachmentPostProcessorsProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->attachmentPostProcessorsProvider = new AttachmentPostProcessorsProvider($this->configManager);
        $this->attachmentPostProcessorsProvider->setFeatureChecker($this->featureChecker);
    }

    /**
     * @dataProvider postProcessingProvider
     */
    public function testPostProcessingEnabled(bool $postProcessingEnabled): void
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with(PostProcessingVoter::ATTACHMENT_POST_PROCESSING)
            ->willReturn($postProcessingEnabled);

        $this->assertEquals($postProcessingEnabled, $this->attachmentPostProcessorsProvider->isPostProcessingEnabled());
        // Need to check whether the parameters are saved locally
        $this->assertEquals($postProcessingEnabled, $this->attachmentPostProcessorsProvider->isPostProcessingEnabled());
    }

    public function postProcessingProvider(): array
    {
        return [
            'Post processing feature enable' => [
                'Processing status' => true,
            ],
            'Post processing feature disable' => [
                'Processing status' => false,
            ],
        ];
    }

    /**
     * @dataProvider postProcessorProvider
     */
    public function testPostProcessorAllowed(bool $postProcessorEnabled): void
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('attachment_post_processors_allowed')
            ->willReturn($postProcessorEnabled);

        $this->assertEquals($postProcessorEnabled, $this->attachmentPostProcessorsProvider->isPostProcessorsAllowed());
        // Need to check whether the parameters are saved locally
        $this->assertEquals($postProcessorEnabled, $this->attachmentPostProcessorsProvider->isPostProcessorsAllowed());
    }

    public function postProcessorProvider(): array
    {
        return [
            'Post processor feature enable' => [
                'post_processor_enabled' => true,
            ],
            'Post processor feature disabled' => [
                'post_processor_enabled' => false,
            ],
        ];
    }

    public function testGetFilterConfig(): void
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('attachment_post_processors_allowed')
            ->willReturn(true);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['oro_attachment.png_quality'],
                ['oro_attachment.jpeg_quality']
            )
            ->willReturnOnConsecutiveCalls(
                Configuration::PNG_QUALITY,
                Configuration::JPEG_QUALITY
            );

        $expected = [
            'pngquant' => ['quality' => Configuration::PNG_QUALITY],
            'jpegoptim' => ['strip_all' => true, 'max' => Configuration::JPEG_QUALITY, 'progressive' => false],
        ];

        $this->assertEquals($expected, $this->attachmentPostProcessorsProvider->getFilterConfig());
    }

    public function testGetFilterConfigWithoutLibraries(): void
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('attachment_post_processors_allowed')
            ->willReturn(false);

        $this->assertEquals([], $this->attachmentPostProcessorsProvider->getFilterConfig());
    }
}
