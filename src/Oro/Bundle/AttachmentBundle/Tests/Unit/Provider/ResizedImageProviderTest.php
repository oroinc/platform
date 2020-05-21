<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProvider;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class ResizedImageProviderTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var ImagineBinaryByFileContentFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagineBinaryFactory;

    /** @var ImagineBinaryFilterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagineBinaryFilter;

    /** @var FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $filterConfig;

    /** @var ResizedImageProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->imagineBinaryFactory = $this->createMock(ImagineBinaryByFileContentFactoryInterface::class);
        $this->imagineBinaryFilter = $this->createMock(ImagineBinaryFilterInterface::class);
        $this->filterConfig = $this->createMock(FilterConfiguration::class);

        $this->provider = new ResizedImageProvider(
            $this->fileManager,
            $this->imagineBinaryFactory,
            $this->imagineBinaryFilter,
            $this->filterConfig
        );

        $this->setUpLoggerMock($this->provider);
    }

    public function testGetFilteredImageWhenCannotApplyFilter(): void
    {
        $image = $this->getImage();

        $this->fileManager
            ->expects(self::once())
            ->method('getContent')
            ->with($image)
            ->willReturn($rawImage = 'sample-image-raw');

        $this->imagineBinaryFactory
            ->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawImage)
            ->willReturn($originalImageBinary = $this->createMock(BinaryInterface::class));

        $this->imagineBinaryFilter
            ->expects(self::once())
            ->method('applyFilter')
            ->with($originalImageBinary, $filterName = 'sample-filter')
            ->willThrowException(new RuntimeException());

        $this->assertLoggerWarningMethodCalled();

        self::assertNull($this->provider->getFilteredImage($image, $filterName));
    }

    /**
     * @return TestFile
     */
    private function getImage(): TestFile
    {
        $image = new TestFile();
        $image->setId($fileId = 1);
        $image->setFilename($filename = 'sample-filename');

        return $image;
    }

    public function testGetFilteredImage(): void
    {
        $image = $this->getImage();

        $this->fileManager
            ->expects(self::once())
            ->method('getContent')
            ->with($image)
            ->willReturn($rawImage = 'sample-image-raw');

        $this->imagineBinaryFactory
            ->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawImage)
            ->willReturn($originalImageBinary = $this->createMock(BinaryInterface::class));

        $this->imagineBinaryFilter
            ->expects(self::once())
            ->method('applyFilter')
            ->with($originalImageBinary, $filterName = 'sample-filter')
            ->willReturn($originalImageBinary = $this->createMock(BinaryInterface::class));

        self::assertSame($originalImageBinary, $this->provider->getFilteredImage($image, $filterName));
    }

    public function testGetResizedImage(): void
    {
        $image = $this->getImage();

        $this->filterConfig
            ->expects(self::once())
            ->method('set')
            ->with(
                $filterName = 'attachment_10_20',
                ['filters' => ['thumbnail' => ['size' => [$width = 10, $height = 20]]]]
            );

        $this->fileManager
            ->expects(self::once())
            ->method('getContent')
            ->with($image)
            ->willReturn($rawImage = 'sample-image-raw');

        $this->imagineBinaryFactory
            ->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawImage)
            ->willReturn($originalImageBinary = $this->createMock(BinaryInterface::class));

        $this->imagineBinaryFilter
            ->expects(self::once())
            ->method('applyFilter')
            ->with($originalImageBinary, $filterName)
            ->willReturn($originalImageBinary = $this->createMock(BinaryInterface::class));

        self::assertSame($originalImageBinary, $this->provider->getResizedImage($image, $width, $height));
    }
}
