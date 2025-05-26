<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistryInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ImageResizeManagerTest extends TestCase
{
    private const int WIDTH = 10;
    private const int HEIGHT = 20;
    private const string FILTER = 'sample-filter';
    private const string FORMAT = 'sample_format';
    private const string STORAGE_PATH = 'sample/storagePath';

    private ResizedImageProviderInterface&MockObject $resizedImageProvider;
    private ResizedImagePathProviderInterface&MockObject $resizedImagePathProvider;
    private MediaCacheManagerRegistryInterface&MockObject $mediaCacheManagerRegistry;
    private ImagineBinaryByFileContentFactoryInterface&MockObject $imagineBinaryFactory;
    private ImageResizeManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->resizedImageProvider = $this->createMock(ResizedImageProviderInterface::class);
        $this->resizedImagePathProvider = $this->createMock(ResizedImagePathProviderInterface::class);
        $this->mediaCacheManagerRegistry = $this->createMock(MediaCacheManagerRegistryInterface::class);
        $this->imagineBinaryFactory = $this->createMock(ImagineBinaryByFileContentFactoryInterface::class);

        $this->manager = new ImageResizeManager(
            $this->resizedImageProvider,
            $this->resizedImagePathProvider,
            $this->mediaCacheManagerRegistry,
            $this->imagineBinaryFactory
        );
    }

    public function testResizeReturnsNullWhenStoredExternally(): void
    {
        $file = new File();
        $file->setExternalUrl('http://example.org/image.png');

        $this->resizedImagePathProvider->expects(self::never())
            ->method(self::anything());

        $this->imagineBinaryFactory->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT));
    }

    public function testResizeWhenAlreadyExists(): void
    {
        $file = new File();
        $rawResizedImage = 'raw-image';
        $this->getMediaCacheManager($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary);

        self::assertSame(
            $imageBinary,
            $this->manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    private function getMediaCacheManager(File $file, string $rawResizedImage): GaufretteFileManager&MockObject
    {
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with($file)
            ->willReturn($mediaCacheManager);

        $mediaCacheManager->expects(self::any())
            ->method('getFileContent')
            ->with(self::STORAGE_PATH, false)
            ->willReturn($rawResizedImage);

        return $mediaCacheManager;
    }

    public function testApplyFilterReturnsNullWhenStoredExternally(): void
    {
        $file = new File();
        $file->setExternalUrl('http://example.org/image.png');

        $this->resizedImagePathProvider->expects(self::never())
            ->method(self::anything());

        $this->imagineBinaryFactory->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->manager->applyFilter($file, self::FILTER, self::FORMAT));
    }

    public function testApplyFilterWhenAlreadyExists(): void
    {
        $file = new File();
        $rawResizedImage = 'raw-image';
        $this->getMediaCacheManager($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary);

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT)
        );
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testResizeWhenFails(string $rawResizedImage, bool $forceUpdate): void
    {
        $file = new File();
        $this->getMediaCacheManager($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(null);

        self::assertNull($this->manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT, $forceUpdate));
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
            ],
        ];
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testResize(string $rawResizedImage, bool $forceUpdate): void
    {
        $file = new File();
        $mediaCacheManager = $this->getMediaCacheManager($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->resizedImageProvider->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn($imageBinary);

        $newResizedImage = 'new-sample-image';
        $imageBinary->expects(self::once())
            ->method('getContent')
            ->willReturn($newResizedImage);

        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        self::assertSame(
            $imageBinary,
            $this->manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT, $forceUpdate)
        );
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testApplyFilterWhenFails(string $rawResizedImage, bool $forceUpdate): void
    {
        $file = new File();
        $this->getMediaCacheManager($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(null);

        self::assertNull($this->manager->applyFilter($file, self::FILTER, self::FORMAT, $forceUpdate));
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testApplyFilter(string $rawResizedImage, bool $forceUpdate): void
    {
        $file = new File();
        $mediaCacheManager = $this->getMediaCacheManager($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->resizedImageProvider->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn($imageBinary);

        $newResizedImage = 'new-sample-image';
        $imageBinary->expects(self::once())
            ->method('getContent')
            ->willReturn($newResizedImage);

        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT, $forceUpdate)
        );
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testApplyFilterWhenFilterInAnotherFormat(string $rawResizedImage, bool $forceUpdate): void
    {
        $file = new File();
        $mediaCacheManager = $this->getMediaCacheManager($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $newResizedImage = 'new-sample-image';
        $imageBinary = new Binary($newResizedImage, 'image/jpg');
        $this->resizedImageProvider->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn($imageBinary);

        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT, $forceUpdate)
        );
    }
}
