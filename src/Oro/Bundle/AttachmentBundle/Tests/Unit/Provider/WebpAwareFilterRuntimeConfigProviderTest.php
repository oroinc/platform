<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\WebpAwareFilterRuntimeConfigProvider;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebpAwareFilterRuntimeConfigProviderTest extends TestCase
{
    private FilterRuntimeConfigProviderInterface&MockObject $innerFilterRuntimeConfigProvider;
    private WebpConfiguration&MockObject $webpConfiguration;
    private WebpAwareFilterRuntimeConfigProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerFilterRuntimeConfigProvider = $this->createMock(FilterRuntimeConfigProviderInterface::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $this->provider = new WebpAwareFilterRuntimeConfigProvider(
            $this->innerFilterRuntimeConfigProvider,
            $this->webpConfiguration
        );
    }

    public function testGetRuntimeConfigForFilterWhenFormatNotWebp(): void
    {
        $filterName = 'sample_filter';
        $format = 'not_webp';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->innerFilterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);

        self::assertEquals($runtimeConfig, $this->provider->getRuntimeConfigForFilter($filterName, $format));
    }

    public function testGetRuntimeConfigForFilterWhenWebpIsDisabled(): void
    {
        $filterName = 'sample_filter';
        $format = 'webp';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->webpConfiguration->expects(self::once())
            ->method('isDisabled')
            ->willReturn(true);

        $this->innerFilterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);

        self::assertEquals($runtimeConfig, $this->provider->getRuntimeConfigForFilter($filterName, $format));
    }

    public function testGetRuntimeConfigForFilterWhenWebpAndNotIsDisabled(): void
    {
        $filterName = 'sample_filter';
        $format = 'webp';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $this->webpConfiguration->expects(self::once())
            ->method('isDisabled')
            ->willReturn(false);

        $webpQuality = 50;
        $this->webpConfiguration->expects(self::once())
            ->method('getWebpQuality')
            ->willReturn($webpQuality);

        $this->innerFilterRuntimeConfigProvider->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);

        self::assertEquals(
            ['format' => 'webp', 'quality' => $webpQuality] + $runtimeConfig,
            $this->provider->getRuntimeConfigForFilter($filterName, $format)
        );
    }
}
