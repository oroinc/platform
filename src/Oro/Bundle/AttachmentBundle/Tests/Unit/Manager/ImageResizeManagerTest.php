<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistryInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

class ImageResizeManagerTest extends \PHPUnit\Framework\TestCase
{
    private const WIDTH = 10;
    private const HEIGHT = 20;
    private const FILTER = 'sample-filter';
    private const STORAGE_PATH = 'sample/storagePath';

    /** @var ResizedImageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $resizedImageProvider;

    /** @var ResizedImagePathProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $resizedImagePathProvider;

    /** @var MediaCacheManagerRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mediaCacheManagerRegistry;

    /** @var ImagineBinaryByFileContentFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagineBinaryByFileContentFactory;

    /** @var ImageResizeManager */
    private $manager;

    protected function setUp(): void
    {
        $this->resizedImageProvider = $this->createMock(ResizedImageProviderInterface::class);
        $this->resizedImagePathProvider = $this->createMock(ResizedImagePathProviderInterface::class);
        $this->mediaCacheManagerRegistry = $this->createMock(MediaCacheManagerRegistryInterface::class);
        $this->imagineBinaryByFileContentFactory = $this->createMock(ImagineBinaryByFileContentFactoryInterface::class);

        $this->manager = new ImageResizeManager(
            $this->resizedImageProvider,
            $this->resizedImagePathProvider,
            $this->mediaCacheManagerRegistry,
            $this->imagineBinaryByFileContentFactory
        );
    }

    public function testResizeWhenAlreadyExists(): void
    {
        $this->mockMediaCacheManager($file = new File(), $rawResizedImage = 'raw-image');

        $this->resizedImagePathProvider
            ->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT)
            ->willReturn(self::STORAGE_PATH);

        $this->imagineBinaryByFileContentFactory
            ->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        self::assertSame(
            $imageBinary,
            $this->manager->resize($file, self::WIDTH, self::HEIGHT)
        );
    }

    /**
     * @param File $file
     * @param string $rawResizedImage
     *
     * @return GaufretteFileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockMediaCacheManager(File $file, string $rawResizedImage): GaufretteFileManager
    {
        $this->mediaCacheManagerRegistry
            ->expects(self::once())
            ->method('getManagerForFile')
            ->with($file)
            ->willReturn($mediaCacheManager = $this->createMock(GaufretteFileManager::class));

        $mediaCacheManager
            ->method('getFileContent')
            ->with(self::STORAGE_PATH, false)
            ->willReturn($rawResizedImage);

        return $mediaCacheManager;
    }

    public function testApplyFilterWhenAlreadyExists(): void
    {
        $this->mockMediaCacheManager($file = new File(), $rawResizedImage = 'raw-image');

        $this->resizedImagePathProvider
            ->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER)
            ->willReturn(self::STORAGE_PATH);

        $this->imagineBinaryByFileContentFactory
            ->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER)
        );
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testResizeWhenFails(string $rawResizedImage, bool $forceUpdate): void
    {
        $this->mockMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider
            ->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider
            ->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT)
            ->willReturn(null);

        self::assertNull($this->manager->resize($file, self::WIDTH, self::HEIGHT, $forceUpdate));
    }

    public function resizeWhenResizeFailsDataProvider(): array
    {
        return [
            [
                'rawResizedImage' => '',
                'forceUpdate' => false,
            ],
            [
                'rawResizedImage' => 'raw-image',
                'forceUpdate' => true,
            ]
        ];
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testResize(string $rawResizedImage, bool $forceUpdate): void
    {
        $mediaCacheManager = $this->mockMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider
            ->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider
            ->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        $imageBinary
            ->expects(self::once())
            ->method('getContent')
            ->willReturn($newResizedImage = 'new-sample-image');

        $mediaCacheManager
            ->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        self::assertSame(
            $imageBinary,
            $this->manager->resize($file, self::WIDTH, self::HEIGHT, $forceUpdate)
        );
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testApplyFilterWhenFails(string $rawResizedImage, bool $forceUpdate): void
    {
        $this->mockMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider
            ->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider
            ->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER)
            ->willReturn(null);

        self::assertNull($this->manager->applyFilter($file, self::FILTER, $forceUpdate));
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testApplyFilter(string $rawResizedImage, bool $forceUpdate): void
    {
        $mediaCacheManager = $this->mockMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider
            ->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider
            ->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        $imageBinary
            ->expects(self::once())
            ->method('getContent')
            ->willReturn($newResizedImage = 'new-sample-image');

        $mediaCacheManager
            ->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, $forceUpdate)
        );
    }
}
