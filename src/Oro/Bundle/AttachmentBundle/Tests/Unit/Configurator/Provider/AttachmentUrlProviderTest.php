<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentHashProvider;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentPostProcessorsProvider;

class AttachmentUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var AttachmentPostProcessorsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentPostProcessorsProvider;

    /** @var AttachmentFilterConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentFilterConfiguration;

    /** @var AttachmentHashProvider */
    private $attachmentHashProvider;

    protected function setUp(): void
    {
        $this->attachmentPostProcessorsProvider = $this->createMock(AttachmentPostProcessorsProvider::class);
        $this->attachmentFilterConfiguration = $this->createMock(AttachmentFilterConfiguration::class);

        $this->attachmentHashProvider = new AttachmentHashProvider(
            $this->attachmentPostProcessorsProvider,
            $this->attachmentFilterConfiguration
        );
    }

    public function testGetUrlFilterConfigWithDefaultSystemConfiguration(): void
    {
        $filterName = 'filterName';
        $filter = ['filterName' => ['option' => 'value']];

        $this->attachmentPostProcessorsProvider
            ->expects($this->once())
            ->method('isPostProcessingEnabled')
            ->willReturn(false);

        $this->attachmentFilterConfiguration
            ->expects($this->never())
            ->method('get');

        $this->attachmentFilterConfiguration
            ->expects($this->once())
            ->method('getOriginal')
            ->with($filterName)
            ->willReturn($filter);

        $hash = $this->attachmentHashProvider->getFilterConfigHash($filterName);
        $this->assertEquals(md5(json_encode($filter)), $hash);
    }

    public function testGetUrlFilterConfigWithChangedSystemConfiguration(): void
    {
        $filterName = 'filterName';
        $filter = ['filterName' => ['option' => 'value']];

        $this->attachmentPostProcessorsProvider
            ->expects($this->once())
            ->method('isPostProcessingEnabled')
            ->willReturn(true);

        $this->attachmentFilterConfiguration
            ->expects($this->once())
            ->method('get')
            ->with($filterName)
            ->willReturn($filter);

        $this->attachmentFilterConfiguration
            ->expects($this->never())
            ->method('getOriginal');

        $hash = $this->attachmentHashProvider->getFilterConfigHash($filterName);
        $this->assertEquals(md5(json_encode($filter)), $hash);
    }
}
