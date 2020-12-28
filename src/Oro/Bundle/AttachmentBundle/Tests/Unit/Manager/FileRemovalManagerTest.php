<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\DirectoryExtractor;
use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\FileRemovalManagerConfigInterface;
use Oro\Bundle\AttachmentBundle\Manager\FileRemovalManager;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistryInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileNamesProviderInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

class FileRemovalManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileRemovalManagerConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configuration;

    /** @var FileNamesProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileNamesProvider;

    /** @var MediaCacheManagerRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $mediaCacheManagerRegistry;

    /** @var FileRemovalManager */
    private $fileRemovalManager;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(FileRemovalManagerConfigInterface::class);
        $this->fileNamesProvider = $this->createMock(FileNamesProviderInterface::class);
        $this->mediaCacheManagerRegistry = $this->createMock(MediaCacheManagerRegistryInterface::class);

        $this->fileRemovalManager = new FileRemovalManager(
            $this->configuration,
            $this->fileNamesProvider,
            $this->mediaCacheManagerRegistry
        );
    }

    public function testRemoveFilesWhenDirectoriesNotMatched()
    {
        $file = $this->createMock(File::class);

        $this->configuration->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->willReturn([
                'resize' => new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true)
            ]);
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with(self::identicalTo($file))
            ->willReturn($mediaCacheManager);
        $this->fileNamesProvider->expects(self::once())
            ->method('getFileNames')
            ->with(self::identicalTo($file))
            ->willReturn([
                'attachment/filter/filter1/hash/123/file.jpg',
                'attachment/filter/filter2/hash/123/file.jpg'
            ]);

        $mediaCacheManager->expects(self::exactly(2))
            ->method('deleteFile')
            ->withConsecutive(
                ['attachment/filter/filter1/hash/123/file.jpg'],
                ['attachment/filter/filter2/hash/123/file.jpg']
            );
        $mediaCacheManager->expects(self::never())
            ->method('deleteAllFiles');

        $this->fileRemovalManager->removeFiles($file);
    }

    public function testRemoveFilesWhenDirectoriesMatchedAndAllowedToUseForSingleFile()
    {
        $file = $this->createMock(File::class);

        $this->configuration->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->willReturn([
                'filter' => new DirectoryExtractor('/^(attachment\/filter\/\w+\/\w+\/\d+)\/\w+/', true)
            ]);
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with(self::identicalTo($file))
            ->willReturn($mediaCacheManager);
        $this->fileNamesProvider->expects(self::once())
            ->method('getFileNames')
            ->with(self::identicalTo($file))
            ->willReturn([
                'attachment/filter/filter1/hash/123/file.jpg',
                'attachment/filter/filter2/hash/123/file1.jpg',
                'attachment/filter/filter2/hash/123/file2.jpg'
            ]);

        $mediaCacheManager->expects(self::never())
            ->method('deleteFile');
        $mediaCacheManager->expects(self::exactly(2))
            ->method('deleteAllFiles')
            ->withConsecutive(
                ['attachment/filter/filter1/hash/123/'],
                ['attachment/filter/filter2/hash/123/']
            );

        $this->fileRemovalManager->removeFiles($file);
    }

    public function testRemoveFilesWhenDirectoriesMatchedAndNotAllowedToUseForSingleFile()
    {
        $file = $this->createMock(File::class);

        $this->configuration->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->willReturn([
                'filter' => new DirectoryExtractor('/^(attachment\/filter\/\w+\/\w+\/\d+)\/\w+/', false)
            ]);
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with(self::identicalTo($file))
            ->willReturn($mediaCacheManager);
        $this->fileNamesProvider->expects(self::once())
            ->method('getFileNames')
            ->with(self::identicalTo($file))
            ->willReturn([
                'attachment/filter/filter1/hash/123/file.jpg',
                'attachment/filter/filter2/hash/123/file1.jpg',
                'attachment/filter/filter2/hash/123/file2.jpg'
            ]);

        $mediaCacheManager->expects(self::once())
            ->method('deleteFile')
            ->with('attachment/filter/filter1/hash/123/file.jpg');
        $mediaCacheManager->expects(self::once())
            ->method('deleteAllFiles')
            ->with('attachment/filter/filter2/hash/123/');

        $this->fileRemovalManager->removeFiles($file);
    }

    public function testRemoveFilesWhenThereAreMatchedDirsAndNotMatchedDirsFilesAndAllowedToUseForSingleFile()
    {
        $file = $this->createMock(File::class);

        $this->configuration->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->willReturn([
                'filter' => new DirectoryExtractor('/^(attachment\/filter\/\w+\/\w+\/\d+)\/\w+/', true),
                'resize' => new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', true)
            ]);
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with(self::identicalTo($file))
            ->willReturn($mediaCacheManager);
        $this->fileNamesProvider->expects(self::once())
            ->method('getFileNames')
            ->with(self::identicalTo($file))
            ->willReturn([
                'attachment/filter/filter1/hash/123/file.jpg',
                'attachment/filter/filter2/hash/123/file1.jpg',
                'attachment/filter/filter2/hash/123/file2.jpg',
                'attachment/resize/123/1/1/file.jpg',
                'attachment/resize/123/10/10/file.jpg',
                'attachment/other/123/1/1/file.jpg',
                'attachment/other/123/file.jpg'
            ]);

        $mediaCacheManager->expects(self::exactly(2))
            ->method('deleteFile')
            ->withConsecutive(
                ['attachment/other/123/1/1/file.jpg'],
                ['attachment/other/123/file.jpg']
            );
        $mediaCacheManager->expects(self::exactly(3))
            ->method('deleteAllFiles')
            ->withConsecutive(
                ['attachment/filter/filter1/hash/123/'],
                ['attachment/filter/filter2/hash/123/'],
                ['attachment/resize/123/']
            );

        $this->fileRemovalManager->removeFiles($file);
    }

    public function testRemoveFilesWhenThereAreMatchedDirsAndNotMatchedDirsFilesAndNotAllowedToUseForSingleFile()
    {
        $file = $this->createMock(File::class);

        $this->configuration->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->willReturn([
                'filter' => new DirectoryExtractor('/^(attachment\/filter\/\w+\/\w+\/\d+)\/\w+/', false),
                'resize' => new DirectoryExtractor('/^(attachment\/resize\/\d+)\/\d+\/\d+\/\w+/', false)
            ]);
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with(self::identicalTo($file))
            ->willReturn($mediaCacheManager);
        $this->fileNamesProvider->expects(self::once())
            ->method('getFileNames')
            ->with(self::identicalTo($file))
            ->willReturn([
                'attachment/filter/filter1/hash/123/file.jpg',
                'attachment/filter/filter2/hash/123/file1.jpg',
                'attachment/filter/filter2/hash/123/file2.jpg',
                'attachment/resize/123/1/1/file.jpg',
                'attachment/resize/123/10/10/file.jpg',
                'attachment/other/123/1/1/file.jpg',
                'attachment/other/123/file.jpg'
            ]);

        $mediaCacheManager->expects(self::exactly(3))
            ->method('deleteFile')
            ->withConsecutive(
                ['attachment/other/123/1/1/file.jpg'],
                ['attachment/other/123/file.jpg'],
                ['attachment/filter/filter1/hash/123/file.jpg']
            );
        $mediaCacheManager->expects(self::exactly(2))
            ->method('deleteAllFiles')
            ->withConsecutive(
                ['attachment/filter/filter2/hash/123/'],
                ['attachment/resize/123/']
            );

        $this->fileRemovalManager->removeFiles($file);
    }

    public function testRemoveFilesWhenDirectoriesMatchedByPatternThatIncludesTailingSlash()
    {
        $file = $this->createMock(File::class);

        $this->configuration->expects(self::atLeastOnce())
            ->method('getConfiguration')
            ->willReturn([
                'filter' => new DirectoryExtractor('/^(\w+\/)\w+/', true)
            ]);
        $mediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->mediaCacheManagerRegistry->expects(self::once())
            ->method('getManagerForFile')
            ->with(self::identicalTo($file))
            ->willReturn($mediaCacheManager);
        $this->fileNamesProvider->expects(self::once())
            ->method('getFileNames')
            ->with(self::identicalTo($file))
            ->willReturn([
                'dir/file.txt'
            ]);

        $mediaCacheManager->expects(self::never())
            ->method('deleteFile');
        $mediaCacheManager->expects(self::once())
            ->method('deleteAllFiles')
            ->with('dir/');

        $this->fileRemovalManager->removeFiles($file);
    }
}
