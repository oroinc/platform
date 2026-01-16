<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeConfigurationProvider;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeContext;

class RuntimeConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    private RuntimeConfigurationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new RuntimeConfigurationProvider([]);
    }

    public function testGetRuntimeConfigWithNoProviders(): void
    {
        $result = $this->provider->getRuntimeConfig('sample_filter', ['key' => 'value']);

        self::assertEquals([], $result);
    }

    public function testGetRuntimeConfigWithSingleSupportedProvider(): void
    {
        $filterName = 'sample_filter';
        $context = ['format' => 'webp'];

        $runtimeProvider = $this->createMock(RuntimeConfigProviderInterface::class);
        $runtimeProvider->expects(self::once())
            ->method('isSupported')
            ->with($filterName)
            ->willReturn(true);

        $expectedConfig = ['format' => 'webp', 'quality' => 85];
        $runtimeProvider->expects(self::once())
            ->method('getRuntimeConfig')
            ->with($filterName, self::isInstanceOf(RuntimeContext::class))
            ->willReturn($expectedConfig);

        $provider = new RuntimeConfigurationProvider([$runtimeProvider]);
        $result = $provider->getRuntimeConfig($filterName, $context);

        self::assertEquals($expectedConfig, $result);
    }

    public function testGetRuntimeConfigSkipsUnsupportedProviders(): void
    {
        $filterName = 'sample_filter';
        $context = ['format' => 'webp'];

        $unsupportedProvider = $this->createMock(RuntimeConfigProviderInterface::class);
        $unsupportedProvider->expects(self::once())
            ->method('isSupported')
            ->with($filterName)
            ->willReturn(false);
        $unsupportedProvider->expects(self::never())
            ->method('getRuntimeConfig');

        $provider = new RuntimeConfigurationProvider([$unsupportedProvider]);
        $result = $provider->getRuntimeConfig($filterName, $context);

        self::assertEquals([], $result);
    }

    public function testGetRuntimeConfigMergesMultipleProviders(): void
    {
        $filterName = 'sample_filter';
        $context = ['format' => 'webp', 'exif_refresh_hash' => true];

        $firstProvider = $this->createMock(RuntimeConfigProviderInterface::class);
        $firstProvider->expects(self::once())
            ->method('isSupported')
            ->with($filterName)
            ->willReturn(true);
        $firstProvider->expects(self::once())
            ->method('getRuntimeConfig')
            ->willReturn(['format' => 'webp', 'quality' => 85]);

        $secondProvider = $this->createMock(RuntimeConfigProviderInterface::class);
        $secondProvider->expects(self::once())
            ->method('isSupported')
            ->with($filterName)
            ->willReturn(true);
        $secondProvider->expects(self::once())
            ->method('getRuntimeConfig')
            ->willReturn(['post_processors' => ['exif_tool_post_processor' => []]]);

        $provider = new RuntimeConfigurationProvider([$firstProvider, $secondProvider]);
        $result = $provider->getRuntimeConfig($filterName, $context);

        $expected = [
            'format' => 'webp',
            'quality' => 85,
            'post_processors' => ['exif_tool_post_processor' => []],
        ];
        self::assertEquals($expected, $result);
    }

    public function testGetRuntimeConfigMergesRecursively(): void
    {
        $filterName = 'sample_filter';
        $context = [];

        $firstProvider = $this->createMock(RuntimeConfigProviderInterface::class);
        $firstProvider->expects(self::once())
            ->method('isSupported')
            ->willReturn(true);
        $firstProvider->expects(self::once())
            ->method('getRuntimeConfig')
            ->willReturn(['post_processors' => ['processor1' => ['key1' => 'value1']]]);

        $secondProvider = $this->createMock(RuntimeConfigProviderInterface::class);
        $secondProvider->expects(self::once())
            ->method('isSupported')
            ->willReturn(true);
        $secondProvider->expects(self::once())
            ->method('getRuntimeConfig')
            ->willReturn(['post_processors' => ['processor2' => ['key2' => 'value2']]]);

        $provider = new RuntimeConfigurationProvider([$firstProvider, $secondProvider]);
        $result = $provider->getRuntimeConfig($filterName, $context);

        $expected = [
            'post_processors' => [
                'processor1' => ['key1' => 'value1'],
                'processor2' => ['key2' => 'value2'],
            ],
        ];
        self::assertEquals($expected, $result);
    }
}
