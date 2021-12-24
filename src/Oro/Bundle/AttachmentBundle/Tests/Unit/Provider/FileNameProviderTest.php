<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProvider;

class FileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject $filterConfiguration;

    protected function setUp(): void
    {
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);

        $this->filterConfiguration
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([['png_filter', ['format' => 'png']], ['empty_filter', []]]);
    }

    public function testGetFileName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename(), $provider->getFileName($file));
    }

    public function testGetFilteredImageName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'empty_filter';

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename(), $provider->getFilteredImageName($file, $filterName));
    }

    public function testGetFilteredImageNameReturnsWithPngExtensionIfFilterHasPngFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'png_filter';

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename() . '.png', $provider->getFilteredImageName($file, $filterName));
    }

    public function testGetFilteredImageNameReturnsUnchangedIfFilterHasPngFormatAndImageToo(): void
    {
        $file = new File();
        $file->setFilename('filename.png');
        $file->setExtension('png');
        $filterName = 'png_filter';

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename(), $provider->getFilteredImageName($file, $filterName));
    }

    public function testGetFilteredImageNameWithWebpExtensionEvenIfFilterHasPngFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.png');
        $file->setExtension('png');
        $filterName = 'png_filter';

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename() . '.webp', $provider->getFilteredImageName($file, $filterName, 'webp'));
    }

    public function testGetFilteredImageNameReturnsUnchangedWhenSameFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'empty_filter';

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename(), $provider->getFilteredImageName($file, $filterName, 'jpeg'));
    }

    public function testGetFilteredImageNameReturnsWithNewExtensionWhenNewFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $filterName = 'empty_filter';

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename() . '.webp', $provider->getFilteredImageName($file, $filterName, 'webp'));
    }

    public function testGetResizedImageName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setExtension('jpeg');
        $width = 42;
        $height = 142;

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename(), $provider->getResizedImageName($file, $width, $height));
    }

    public function testGetResizedImageNameReturnsUnchangedWhenSameFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $width = 42;
        $height = 142;

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame($file->getFilename(), $provider->getResizedImageName($file, $width, $height, 'jpeg'));
    }

    public function testGetResizedImageNameReturnsWithNewExtensionWhenNewFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');
        $width = 42;
        $height = 142;

        $provider = new FileNameProvider($this->filterConfiguration);
        self::assertSame(
            $file->getFilename() . '.webp',
            $provider->getResizedImageName($file, $width, $height, 'webp')
        );
    }
}
