<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentHashProvider;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentPostProcessorsProvider;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\RuntimeConfigurationProvider;
use Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;

class AttachmentHashProviderTest extends \PHPUnit\Framework\TestCase
{
    private AttachmentPostProcessorsProvider&MockObject $attachmentPostProcessorsProvider;
    private AttachmentFilterConfiguration&MockObject $attachmentFilterConfiguration;
    private FilterRuntimeConfigProviderInterface&MockObject $filterRuntimeConfigProvider;
    private RuntimeConfigurationProvider&MockObject $runtimeConfigurationProvider;
    private AttachmentHashProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->attachmentPostProcessorsProvider = $this->createMock(AttachmentPostProcessorsProvider::class);
        $this->attachmentFilterConfiguration = $this->createMock(AttachmentFilterConfiguration::class);
        $this->filterRuntimeConfigProvider = $this->createMock(FilterRuntimeConfigProviderInterface::class);
        $this->runtimeConfigurationProvider = $this->createMock(RuntimeConfigurationProvider::class);

        $this->provider = new AttachmentHashProvider(
            $this->attachmentPostProcessorsProvider,
            $this->attachmentFilterConfiguration,
            $this->filterRuntimeConfigProvider
        );
        $this->provider->setRuntimeConfigurationProvider($this->runtimeConfigurationProvider);
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
        $this->runtimeConfigurationProvider
            ->expects(self::once())
            ->method('getRuntimeConfig')
            ->with($filterName, ['metadata_refresh_hash' => true, 'format' => $format])
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
        $this->runtimeConfigurationProvider
            ->expects(self::once())
            ->method('getRuntimeConfig')
            ->with($filterName, ['metadata_refresh_hash' => true, 'format' => $format])
            ->willReturn($runtimeConfig);

        self::assertEquals(
            md5(json_encode(array_replace_recursive($filterConfig, $runtimeConfig))),
            $this->provider->getFilterConfigHash($filterName, $format)
        );
    }
}
