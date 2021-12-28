<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProvider;
use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Imagine\Provider\WebpAwareImagineUrlProvider;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebpAwareImagineUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    private FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject $filterConfiguration;

    private ImagineUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerImagineUrlProvider;

    private WebpConfiguration|\PHPUnit\Framework\MockObject\MockObject $webpConfiguration;

    private WebpAwareImagineUrlProvider $provider;

    protected function setUp(): void
    {
        $this->innerImagineUrlProvider = $this->createMock(ImagineUrlProvider::class);
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);
        $this->webpConfiguration = $this->createMock(WebpConfiguration::class);

        $this->provider = new WebpAwareImagineUrlProvider(
            $this->innerImagineUrlProvider,
            $this->filterConfiguration,
            $this->webpConfiguration
        );

        $this->filterConfiguration
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([['jpeg_filter', ['format' => 'jpeg']], ['empty_filter', []]]);
    }

    public function testGetFilteredImageUrlNotAddsWebpWhenNotEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(false);

        $file = new File();
        $filename = 'sample.jpg';
        $filterName = 'empty_filter';
        $this->innerImagineUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filterName, '')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageUrl($file, $filterName, ''));
    }

    public function testGetFilteredImageUrlNotAddsWebpWhenFormatAndEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::never())
            ->method('isEnabledForAll');

        $file = new File();
        $filename = 'sample.jpg';
        $format = 'sample_format';
        $filterName = 'empty_filter';
        $this->innerImagineUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filterName, $format)
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageUrl($file, $filterName, $format));
    }

    public function testGetFilteredImageUrlNotAddsWebpWhenNoFormatAndEnabledForAllButFilterHasFormat(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(true);

        $file = new File();
        $filename = 'sample.jpg';
        $filterName = 'jpeg_filter';
        $this->innerImagineUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filterName, '')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageUrl($file, $filterName, ''));
    }

    public function testGetFilteredImageUrlAddsWebpWhenNoFormatAndEnabledForAll(): void
    {
        $this->webpConfiguration
            ->expects(self::once())
            ->method('isEnabledForAll')
            ->willReturn(true);

        $file = new File();
        $filename = 'sample.jpg';
        $filterName = 'empty_filter';
        $this->innerImagineUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filterName, 'webp')
            ->willReturn($filename);

        self::assertEquals($filename, $this->provider->getFilteredImageUrl($file, $filterName, ''));
    }

    public function getFilteredImageUrl(
        string $path,
        string $filterName,
        string $format = '',
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ): string {
        if (!$format && $this->webpConfiguration->isEnabledForAll()) {
            $filterFormat = $this->filterConfiguration->get($filterName)['format'] ?? '';
            if (!$filterFormat) {
                $format = 'webp';
            }
        }

        return $this->innerImagineUrlProvider->getFilteredImageUrl($path, $filterName, $format, $referenceType);
    }
}
