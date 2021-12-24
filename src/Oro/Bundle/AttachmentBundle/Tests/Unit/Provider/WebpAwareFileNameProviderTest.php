<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
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
        $filterConfiguration = $this->createMock(FilterConfiguration::class);

        $this->provider = new WebpAwareFileNameProvider(
            $this->innerFileNameProvider,
            $this->webpConfiguration,
            $filterConfiguration
        );

        $filterConfiguration
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([['jpeg_filter', ['format' => 'jpeg']], ['empty_filter', []]]);
    }

    public function testGetFileName(): void
    {
        $file = new File();
        $filename = 'file.pdf';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFileName')
            ->with($file)
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFileName($file));
    }

    public function testGetFilteredImageNameNotAddsWebpWhenNotEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(false);

        $file = new File();
        $filename = 'sample.jpg';
        $filterName = 'empty_filter';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, $filterName, '')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageName($file, $filterName, ''));
    }

    public function testGetFilteredImageNameNotAddsWebpWhenFormatAndEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::never())
            ->method('isEnabledForAll');

        $file = new File();
        $filename = 'sample.jpg';
        $format = 'sample_format';
        $filterName = 'empty_filter';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, $filterName, $format)
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageName($file, $filterName, $format));
    }

    public function testGetFilteredImageNameNotAddsWebpWhenNoFormatAndEnabledForAllButFilterHasFormat(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(true);

        $file = new File();
        $filename = 'sample.jpg';
        $filterName = 'jpeg_filter';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, $filterName, '')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageName($file, $filterName, ''));
    }

    public function testGetFilteredImageNameAddsWebpWhenNoFormatAndEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(true);

        $file = new File();
        $filename = 'sample.jpg';
        $filterName = 'empty_filter';
        $this->innerFileNameProvider
            ->expects(self::once())
            ->method('getFilteredImageName')
            ->with($file, $filterName, 'webp')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageName($file, $filterName, ''));
    }
}
