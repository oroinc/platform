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
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

class ImageResizeManagerTest extends \PHPUnit\Framework\TestCase
{
    private const WIDTH = 10;
    private const HEIGHT = 20;
    private const FILTER = 'sample-filter';
    private const FORMAT = 'sample_format';
    private const STORAGE_PATH = 'sample/storagePath';
    private const NON_ASCII_STORAGE_PATH = 'sample/café.jpg';
    private const LEGACY_STORAGE_PATH = 'sample/caf%C3%A9.jpg';

    private ResizedImageProviderInterface|\PHPUnit\Framework\MockObject\MockObject $resizedImageProvider;

    private ResizedImagePathProviderInterface|\PHPUnit\Framework\MockObject\MockObject $resizedImagePathProvider;

    private MediaCacheManagerRegistryInterface|\PHPUnit\Framework\MockObject\MockObject $mediaCacheManagerRegistry;

    private ImagineBinaryByFileContentFactoryInterface|\PHPUnit\Framework\MockObject\MockObject $imagineBinaryFactory;

    private LockFactory|MockObject $lockFactory;

    private ImageResizeManager $manager;

    protected function setUp(): void
    {
        $this->resizedImageProvider = $this->createMock(ResizedImageProviderInterface::class);
        $this->resizedImagePathProvider = $this->createMock(ResizedImagePathProviderInterface::class);
        $this->mediaCacheManagerRegistry = $this->createMock(MediaCacheManagerRegistryInterface::class);
        $this->imagineBinaryFactory = $this->createMock(ImagineBinaryByFileContentFactoryInterface::class);
        $this->lockFactory = $this->createMock(LockFactory::class);

        $this->manager = new ImageResizeManager(
            $this->resizedImageProvider,
            $this->resizedImagePathProvider,
            $this->mediaCacheManagerRegistry,
            $this->imagineBinaryFactory
        );
        $this->manager->setLockFactory($this->lockFactory);
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
        $this->getMediaCacheManager($file = new File(), $rawResizedImage = 'raw-image');

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        $this->lockFactory->expects(self::never())
            ->method('createLock');

        self::assertSame(
            $imageBinary,
            $this->manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
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
        $this->getMediaCacheManager($file = new File(), $rawResizedImage = 'raw-image');

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        $this->lockFactory->expects(self::never())
            ->method('createLock');

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
        $this->getMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(null);

        $this->lockFactory->expects(self::never())
            ->method('createLock');

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
        $mediaCacheManager = $this->getMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        $imageBinary->expects(self::once())
            ->method('getContent')
            ->willReturn($newResizedImage = 'new-sample-image');

        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release');
        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($lock);

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
        $this->getMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(null);

        $this->lockFactory->expects(self::never())
            ->method('createLock');

        self::assertNull($this->manager->applyFilter($file, self::FILTER, self::FORMAT, $forceUpdate));
    }

    /**
     * @dataProvider resizeWhenResizeFailsDataProvider
     */
    public function testApplyFilter(string $rawResizedImage, bool $forceUpdate): void
    {
        $mediaCacheManager = $this->getMediaCacheManager($file = new File(), $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $this->resizedImageProvider->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn($imageBinary = $this->createMock(BinaryInterface::class));

        $imageBinary->expects(self::once())
            ->method('getContent')
            ->willReturn($newResizedImage = 'new-sample-image');

        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release');
        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($lock);

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
        $mediaCacheManager = $this->getMediaCacheManager($file = new File(), $rawResizedImage);

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

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release');
        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($lock);

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT, $forceUpdate)
        );
    }

    public function testResizeWithoutLockFactory(): void
    {
        $manager = new ImageResizeManager(
            $this->resizedImageProvider,
            $this->resizedImagePathProvider,
            $this->mediaCacheManagerRegistry,
            $this->imagineBinaryFactory
        );

        $mediaCacheManager = $this->getMediaCacheManager($file = new File(), '');

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $imageBinary->expects(self::once())
            ->method('getContent')
            ->willReturn($newResizedImage = 'new-sample-image');

        $this->resizedImageProvider->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn($imageBinary);

        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($newResizedImage, self::STORAGE_PATH);

        $this->lockFactory->expects(self::never())
            ->method('createLock');

        self::assertSame(
            $imageBinary,
            $manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT, true)
        );
    }

    public function testResizeSkipsWriteWhenAnotherProcessStoredImageWhileResizing(): void
    {
        $file = new File();
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with($file)
            ->willReturn($mediaCacheManager);

        $mediaCacheManager->expects(self::exactly(2))
            ->method('getFileContent')
            ->with(self::STORAGE_PATH, false)
            ->willReturnOnConsecutiveCalls(null, 'cached-by-another-process');

        $mediaCacheManager->expects(self::once())
            ->method('getFilePathWithoutProtocol')
            ->with(self::STORAGE_PATH)
            ->willReturn('public_mediacache/' . self::STORAGE_PATH);

        $mediaCacheManager->expects(self::never())
            ->method('writeToStorage');

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $imageBinary = new Binary('new-image', 'image/png');
        $this->resizedImageProvider->expects(self::once())
            ->method('getResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn($imageBinary);

        $cachedBinary = $this->createMock(BinaryInterface::class);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with('cached-by-another-process')
            ->willReturn($cachedBinary);

        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release');
        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($lock);

        self::assertSame(
            $cachedBinary,
            $this->manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testResizeMovesImageStoredUnderLegacyPath(): void
    {
        $file = new File();
        $rawResizedImage = 'legacy-image';
        $mediaCacheManager = $this->getMediaCacheManagerWithLegacyImage($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForResizedImage')
            ->with($file, self::WIDTH, self::HEIGHT, self::FORMAT)
            ->willReturn(self::NON_ASCII_STORAGE_PATH);

        $this->resizedImageProvider->expects(self::never())
            ->method(self::anything());

        $mediaCacheManager->expects(self::once())
            ->method('hasFile')
            ->with(self::NON_ASCII_STORAGE_PATH)
            ->willReturn(false);
        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($rawResizedImage, self::NON_ASCII_STORAGE_PATH);
        $mediaCacheManager->expects(self::once())
            ->method('deleteFile')
            ->with(self::LEGACY_STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary);

        $this->expectLock();

        self::assertSame(
            $imageBinary,
            $this->manager->resize($file, self::WIDTH, self::HEIGHT, self::FORMAT)
        );
    }

    public function testApplyFilterMovesImageStoredUnderLegacyPath(): void
    {
        $file = new File();
        $rawResizedImage = 'legacy-image';
        $mediaCacheManager = $this->getMediaCacheManagerWithLegacyImage($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::NON_ASCII_STORAGE_PATH);

        $this->resizedImageProvider->expects(self::never())
            ->method(self::anything());

        $mediaCacheManager->expects(self::once())
            ->method('hasFile')
            ->with(self::NON_ASCII_STORAGE_PATH)
            ->willReturn(false);
        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with($rawResizedImage, self::NON_ASCII_STORAGE_PATH);
        $mediaCacheManager->expects(self::once())
            ->method('deleteFile')
            ->with(self::LEGACY_STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary);

        $this->expectLock();

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT)
        );
    }

    public function testApplyFilterDoesNotOverwriteImageStoredByAnotherProcessWhileMovingLegacyImage(): void
    {
        $file = new File();
        $rawResizedImage = 'legacy-image';
        $mediaCacheManager = $this->getMediaCacheManagerWithLegacyImage($file, $rawResizedImage);

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::NON_ASCII_STORAGE_PATH);

        $mediaCacheManager->expects(self::once())
            ->method('hasFile')
            ->with(self::NON_ASCII_STORAGE_PATH)
            ->willReturn(true);
        $mediaCacheManager->expects(self::never())
            ->method('writeToStorage');
        $mediaCacheManager->expects(self::once())
            ->method('deleteFile')
            ->with(self::LEGACY_STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->imagineBinaryFactory->expects(self::once())
            ->method('createImagineBinary')
            ->with($rawResizedImage)
            ->willReturn($imageBinary);

        $this->expectLock();

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT)
        );
    }

    public function testApplyFilterResizesWhenNeitherCurrentNorLegacyImageExists(): void
    {
        $file = new File();
        $mediaCacheManager = $this->getMediaCacheManagerWithLegacyImage($file, '');

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::NON_ASCII_STORAGE_PATH);

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
            ->with($newResizedImage, self::NON_ASCII_STORAGE_PATH);
        $mediaCacheManager->expects(self::never())
            ->method('deleteFile');

        $this->expectLock();

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT)
        );
    }

    public function testApplyFilterDeletesLegacyImageWhenForceUpdate(): void
    {
        $file = new File();
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with($file)
            ->willReturn($mediaCacheManager);
        $mediaCacheManager->expects(self::never())
            ->method('getFileContent');

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::NON_ASCII_STORAGE_PATH);

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
            ->with($newResizedImage, self::NON_ASCII_STORAGE_PATH);
        $mediaCacheManager->expects(self::once())
            ->method('deleteFile')
            ->with(self::LEGACY_STORAGE_PATH);

        $this->expectLock();

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT, true)
        );
    }

    public function testApplyFilterDoesNotCheckLegacyPathForAsciiPath(): void
    {
        $file = new File();
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with($file)
            ->willReturn($mediaCacheManager);
        $mediaCacheManager->expects(self::exactly(2))
            ->method('getFileContent')
            ->with(self::STORAGE_PATH, false)
            ->willReturn(null);
        $mediaCacheManager->expects(self::never())
            ->method('deleteFile');

        $this->resizedImagePathProvider->expects(self::once())
            ->method('getPathForFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn(self::STORAGE_PATH);

        $imageBinary = $this->createMock(BinaryInterface::class);
        $this->resizedImageProvider->expects(self::once())
            ->method('getFilteredImage')
            ->with($file, self::FILTER, self::FORMAT)
            ->willReturn($imageBinary);
        $imageBinary->expects(self::once())
            ->method('getContent')
            ->willReturn('new-sample-image');

        $mediaCacheManager->expects(self::once())
            ->method('writeToStorage')
            ->with('new-sample-image', self::STORAGE_PATH);

        $this->expectLock();

        self::assertSame(
            $imageBinary,
            $this->manager->applyFilter($file, self::FILTER, self::FORMAT)
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

    private function getMediaCacheManagerWithLegacyImage(
        File $file,
        string $rawLegacyImage
    ): GaufretteFileManager&MockObject {
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with($file)
            ->willReturn($mediaCacheManager);

        $mediaCacheManager->expects(self::any())
            ->method('getFileContent')
            ->willReturnMap([
                [self::NON_ASCII_STORAGE_PATH, false, null],
                [self::LEGACY_STORAGE_PATH, false, $rawLegacyImage ?: null],
            ]);

        return $mediaCacheManager;
    }

    private function expectLock(): void
    {
        $lock = $this->createMock(SharedLockInterface::class);
        $lock->expects(self::once())
            ->method('acquire')
            ->with(true)
            ->willReturn(true);
        $lock->expects(self::once())
            ->method('release');
        $this->lockFactory->expects(self::once())
            ->method('createLock')
            ->willReturn($lock);
    }
}
