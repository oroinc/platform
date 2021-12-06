<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\WebpAwareFileNameProvider;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

class WebpAwareFileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileNameProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerFileNameProvider;

    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private WebpAwareFileNameProvider $provider;

    protected function setUp(): void
    {
        $this->innerFileNameProvider = $this->createMock(FileNameProviderInterface::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $this->provider = new WebpAwareFileNameProvider($this->innerFileNameProvider, $this->webpConfiguration);
    }

    public function testGetFileNameNotAddsWebpWhenNotEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(false);

        $file = new File();
        $filename = 'sample.jpg';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFileName')
            ->with($file, '')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFileName($file, ''));
    }

    public function testGetFileNameNotAddsWebpWhenFormatAndEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::never())
            ->method('isEnabledForAll');

        $file = new File();
        $filename = 'sample.jpg';
        $format = 'sample_format';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFileName')
            ->with($file, $format)
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFileName($file, $format));
    }

    public function testGetFileNameAddsWebpWhenNoFormatAndEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(true);

        $file = new File();
        $filename = 'sample.jpg';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFileName')
            ->with($file, 'webp')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFileName($file, ''));
    }
}
