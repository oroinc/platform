<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentHashProvider;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentPostProcessorsProvider;
use Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigProviderInterface;

class AttachmentHashProviderTest extends \PHPUnit\Framework\TestCase
{
    private AttachmentPostProcessorsProvider|\PHPUnit\Framework\MockObject\MockObject $attachmentPostProcessorsProvider;

    private AttachmentFilterConfiguration|\PHPUnit\Framework\MockObject\MockObject $attachmentFilterConfiguration;

    private FilterRuntimeConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject $filterRuntimeConfigProvider;

    private AttachmentHashProvider $provider;

    protected function setUp(): void
    {
        $this->attachmentPostProcessorsProvider = $this->createMock(AttachmentPostProcessorsProvider::class);
        $this->attachmentFilterConfiguration = $this->createMock(AttachmentFilterConfiguration::class);
        $this->filterRuntimeConfigProvider = $this->createMock(FilterRuntimeConfigProviderInterface::class);

        $this->provider = new AttachmentHashProvider(
            $this->attachmentPostProcessorsProvider,
            $this->attachmentFilterConfiguration,
            $this->filterRuntimeConfigProvider
        );
    }

    public function testGetFilterConfigHashUsesModifiedConfigWhenIsPostProcessingEnabled(): void
    {
        $this->attachmentPostProcessorsProvider
            ->expects(self::once())
            ->method('isPostProcessingEnabled')
            ->willReturn(true);

        $filterName = 'sample_filter';
        $filterConfig = ['sample_key' => 'sample_value'];
        $this->attachmentFilterConfiguration
            ->expects(self::once())
            ->method('get')
            ->with($filterName)
            ->willReturn($filterConfig);

        $format = 'sample_format';
        $runtimeConfig = ['quality' => 50];
        $this->filterRuntimeConfigProvider
            ->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);

        self::assertEquals(
            md5(json_encode(array_replace_recursive($filterConfig, $runtimeConfig))),
            $this->provider->getFilterConfigHash($filterName, $format)
        );
    }

    public function testGetFilterConfigHashUsesModifiedConfigWhenIsPostProcessingDisabled(): void
    {
        $this->attachmentPostProcessorsProvider
            ->expects(self::once())
            ->method('isPostProcessingEnabled')
            ->willReturn(false);

        $filterName = 'sample_filter';
        $filterConfig = ['sample_key' => 'sample_value'];
        $this->attachmentFilterConfiguration
            ->expects(self::once())
            ->method('getOriginal')
            ->with($filterName)
            ->willReturn($filterConfig);

        $format = 'sample_format';
        $runtimeConfig = ['sample_key' => 'sample_runtime_value'];
        $this->filterRuntimeConfigProvider
            ->expects(self::once())
            ->method('getRuntimeConfigForFilter')
            ->with($filterName, $format)
            ->willReturn($runtimeConfig);

        self::assertEquals(
            md5(json_encode(array_replace_recursive($filterConfig, $runtimeConfig))),
            $this->provider->getFilterConfigHash($filterName, $format)
        );
    }
}
