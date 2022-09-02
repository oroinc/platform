<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProvider;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileNameProvider $fileNameProvider;

    protected function setUp(): void
    {
        $filterConfiguration = $this->createMock(FilterConfiguration::class);
        $filterConfiguration
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([['png_filter', ['format' => 'png']], ['empty_filter', []]]);
        $filenameExtensionHelper = new FilenameExtensionHelper(['image/svg']);

        $this->fileNameProvider = new FileNameProvider($filterConfiguration, $filenameExtensionHelper);
    }

    public function testGetFileName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');

        self::assertSame($file->getFilename(), $this->fileNameProvider->getFileName($file));
    }

    public function testGetFilteredImageName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'empty_filter';

        self::assertSame($file->getFilename(), $this->fileNameProvider->getFilteredImageName($file, $filterName));
    }

    public function testGetFilteredImageNameReturnsWithPngExtensionIfFilterHasPngFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'png_filter';

        self::assertSame(
            $file->getFilename() . '.png',
            $this->fileNameProvider->getFilteredImageName($file, $filterName)
        );
    }

    public function testGetFilteredImageNameReturnsUnchangedIfFilterHasPngFormatAndImageToo(): void
    {
        $file = new File();
        $file->setFilename('filename.png');
        $file->setExtension('png');
        $filterName = 'png_filter';

        self::assertSame($file->getFilename(), $this->fileNameProvider->getFilteredImageName($file, $filterName));
    }

    public function testGetFilteredImageNameReturnsUnchangedIfFileHasUnsupportedMimeType(): void
    {
        $file = new File();
        $file->setFilename('filename.svg');
        $file->setExtension('svg');
        $file->setMimeType('image/svg');
        $filterName = 'png_filter';

        self::assertSame($file->getFilename(), $this->fileNameProvider->getFilteredImageName($file, $filterName));
    }

    public function testGetFilteredImageNameWithWebpExtensionEvenIfFilterHasPngFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.png');
        $file->setExtension('png');
        $filterName = 'png_filter';

        self::assertSame(
            $file->getFilename() . '.webp',
            $this->fileNameProvider->getFilteredImageName($file, $filterName, 'webp')
        );
    }

    public function testGetFilteredImageNameReturnsUnchangedWhenSameFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'empty_filter';

        self::assertSame(
            $file->getFilename(),
            $this->fileNameProvider->getFilteredImageName($file, $filterName, 'jpeg')
        );
    }

    public function testGetFilteredImageNameReturnsWithNewExtensionWhenNewFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'empty_filter';

        self::assertSame(
            $file->getFilename() . '.webp',
            $this->fileNameProvider->getFilteredImageName($file, $filterName, 'webp')
        );
    }

    public function testGetResizedImageName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $width = 42;
        $height = 142;

        self::assertSame($file->getFilename(), $this->fileNameProvider->getResizedImageName($file, $width, $height));
    }

    public function testGetResizedImageNameReturnsUnchangedWhenSameFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $width = 42;
        $height = 142;

        self::assertSame(
            $file->getFilename(),
            $this->fileNameProvider->getResizedImageName($file, $width, $height, 'jpeg')
        );
    }

    public function testGetResizedImageNameReturnsUnchangedIfFileHasUnsupportedMimeType(): void
    {
        $file = new File();
        $file->setFilename('filename.svg');
        $file->setOriginalFilename('original-filename.svg');
        $file->setExtension('svg');
        $file->setMimeType('image/svg');
        $width = 42;
        $height = 142;

        self::assertSame(
            $file->getFilename(),
            $this->fileNameProvider->getResizedImageName($file, $width, $height, 'webp')
        );
    }

    public function testGetResizedImageNameReturnsWithNewExtensionWhenNewFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $width = 42;
        $height = 142;

        self::assertSame(
            $file->getFilename() . '.webp',
            $this->fileNameProvider->getResizedImageName($file, $width, $height, 'webp')
        );
    }
}
