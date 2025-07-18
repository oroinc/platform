<?php

declare(strict_types=1);

namespace Oro\Bundle\PdfGeneratorBundle\Tests\Unit\PdfTemplateRenderer\Twig;

use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Twig\FileExtension;
use Oro\Bundle\PdfGeneratorBundle\Exception\PdfTemplateAssetException;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateAsset\PdfTemplateAsset;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\AssetsCollector\PdfTemplateAssetsCollectorInterface;
use Oro\Bundle\PdfGeneratorBundle\PdfTemplateRenderer\Twig\PdfTemplateAssetsCollectorExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Asset\Packages;

final class PdfTemplateAssetsCollectorExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private Packages&MockObject $packages;

    private ImageResizeManagerInterface&MockObject $imageResizeManager;

    private PdfTemplateAssetsCollectorInterface&MockObject $pdfTemplateAssetsCollector;

    private PdfTemplateAssetsCollectorExtension $extension;

    protected function setUp(): void
    {
        $this->packages = $this->createMock(Packages::class);
        $this->imageResizeManager = $this->createMock(ImageResizeManagerInterface::class);
        $this->pdfTemplateAssetsCollector = $this->createMock(PdfTemplateAssetsCollectorInterface::class);

        $this->extension = new PdfTemplateAssetsCollectorExtension(
            $this->packages,
            $this->imageResizeManager,
            $this->pdfTemplateAssetsCollector
        );
    }

    public function testGetAssetUrl(): void
    {
        $path = 'some/path.css';
        $url = '/static/some/path.css';
        $expectedAsset = 'collected/path.css';

        $this->packages
            ->expects(self::once())
            ->method('getUrl')
            ->with($path, null)
            ->willReturn($url);

        $this->pdfTemplateAssetsCollector
            ->expects(self::once())
            ->method('addStaticAsset')
            ->with($url)
            ->willReturn($expectedAsset);

        $result = self::callTwigFunction($this->extension, 'asset', [$path]);
        self::assertEquals($expectedAsset, $result);
    }

    public function testGetFilteredImageUrl(): void
    {
        $file = (new File())->setFilename('image.jpg');
        $binary = new Binary('binary-data', 'image/png');

        $this->imageResizeManager
            ->expects(self::once())
            ->method('applyFilter')
            ->with($file, 'filterName', 'jpeg')
            ->willReturn($binary);

        $this->pdfTemplateAssetsCollector
            ->expects(self::once())
            ->method('addRawAsset')
            ->with('binary-data', 'image.jpg');

        $result = self::callTwigFunction($this->extension, 'filtered_image_url', [$file, 'filterName', 'jpeg']);
        self::assertEquals('image.jpg', $result);
    }

    public function testGetFilteredImageUrlThrowsException(): void
    {
        $file = (new File())->setFilename('image.jpg');

        $this->imageResizeManager
            ->expects(self::once())
            ->method('applyFilter')
            ->willThrowException(new \RuntimeException('Some error'));

        $this->expectException(PdfTemplateAssetException::class);
        $this->expectExceptionMessage(
            'Failed to add a PDF template asset for: file="image.jpg", filter="filterName", format="jpeg"'
        );

        self::callTwigFunction($this->extension, 'filtered_image_url', [$file, 'filterName', 'jpeg']);
    }

    public function testGetResizedImageUrl(): void
    {
        $file = (new File())->setFilename('image.jpg');
        $binary = new Binary('binary-data', 'image/png');

        $this->imageResizeManager
            ->expects(self::once())
            ->method('resize')
            ->with($file, FileExtension::DEFAULT_THUMB_SIZE, FileExtension::DEFAULT_THUMB_SIZE, 'jpeg')
            ->willReturn($binary);

        $this->pdfTemplateAssetsCollector
            ->expects(self::once())
            ->method('addRawAsset')
            ->with('binary-data', 'image.jpg');

        $result = self::callTwigFunction(
            $this->extension,
            'resized_image_url',
            [$file, FileExtension::DEFAULT_THUMB_SIZE, FileExtension::DEFAULT_THUMB_SIZE, 'jpeg']
        );
        self::assertEquals('image.jpg', $result);
    }

    public function testGetAssets(): void
    {
        $assets = ['asset1' => new PdfTemplateAsset('main.css', null, $this->createMock(StreamInterface::class))];
        $this->pdfTemplateAssetsCollector
            ->expects(self::once())
            ->method('getAssets')
            ->willReturn($assets);

        $result = $this->extension->getAssets();
        self::assertSame($assets, $result);
    }

    public function testReset(): void
    {
        $this->pdfTemplateAssetsCollector
            ->expects(self::once())
            ->method('reset');

        $this->extension->reset();
    }
}
