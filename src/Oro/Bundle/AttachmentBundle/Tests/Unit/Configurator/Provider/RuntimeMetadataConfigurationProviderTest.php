<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeContext;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeMetadataConfigurationProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class RuntimeMetadataConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    private RuntimeMetadataConfigurationProvider $provider;
    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;
    private FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->provider = new RuntimeMetadataConfigurationProvider($this->configManager);
        $this->provider->setFeatureChecker($this->featureChecker);
    }

    public function testIsSupportedWhenFeaturesEnabledAndMetadataServiceAllowed(): void
    {
        $this->provider->addFeature('sample_feature');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('sample_feature', null)
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_attachment.metadata_service_allowed')
            ->willReturn(true);

        self::assertTrue($this->provider->isSupported('sample_filter'));
    }

    public function testIsSupportedWhenFeaturesDisabled(): void
    {
        $this->provider->addFeature('sample_feature');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('sample_feature', null)
            ->willReturn(false);

        $this->configManager->expects(self::never())
            ->method('get');

        self::assertFalse($this->provider->isSupported('sample_filter'));
    }

    public function testIsSupportedWhenMetadataServiceNotAllowed(): void
    {
        $this->provider->addFeature('sample_feature');

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('sample_feature', null)
            ->willReturn(true);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_attachment.metadata_service_allowed')
            ->willReturn(false);

        self::assertFalse($this->provider->isSupported('sample_filter'));
    }

    public function testGetRuntimeConfigWithOriginalContent(): void
    {
        $originalBinary = $this->createMock(BinaryInterface::class);
        $originalBinary->expects(self::once())
            ->method('getContent')
            ->willReturn('binary_content');

        $context = new RuntimeContext(['original_content' => $originalBinary]);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        $expected = [
            'post_processors' => [
                'oro_metadata_service' => [
                    'original_content' => 'binary_content',
                    'file_name' => null
                ]
            ]
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetRuntimeConfigWithMetadataRefreshHash(): void
    {
        $context = new RuntimeContext(['metadata_refresh_hash' => true]);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        $expected = [
            'post_processors' => [
                'oro_metadata_service' => [
                    'original_content' => null,
                    'file_name' => null
                ]
            ]
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetRuntimeConfigWithoutRelevantContext(): void
    {
        $context = new RuntimeContext(['format' => 'webp']);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        self::assertEquals([], $result);
    }

    public function testGetRuntimeConfigWithEmptyContext(): void
    {
        $context = new RuntimeContext([]);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        self::assertEquals([], $result);
    }

    public function testGetRuntimeConfigPrefersOriginalContentOverMetadataRefreshHash(): void
    {
        $originalBinary = $this->createMock(BinaryInterface::class);
        $originalBinary->expects(self::once())
            ->method('getContent')
            ->willReturn('binary_content');

        $context = new RuntimeContext([
            'original_content' => $originalBinary,
            'metadata_refresh_hash' => true
        ]);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        $expected = [
            'post_processors' => [
                'oro_metadata_service' => [
                    'original_content' => 'binary_content',
                    'file_name' => null
                ]
            ]
        ];

        self::assertEquals($expected, $result);
    }

    public function testGetRuntimeConfigWithOriginalContentAndFileName(): void
    {
        $originalBinary = $this->createMock(BinaryInterface::class);
        $originalBinary->expects(self::once())
            ->method('getContent')
            ->willReturn('binary_content');

        $context = new RuntimeContext([
            'original_content' => $originalBinary,
            'file_name' => 'test.jpg'
        ]);

        $result = $this->provider->getRuntimeConfig('sample_filter', $context);

        $expected = [
            'post_processors' => [
                'oro_metadata_service' => [
                    'original_content' => 'binary_content',
                    'file_name' => 'test.jpg'
                ]
            ]
        ];

        self::assertEquals($expected, $result);
    }
}
