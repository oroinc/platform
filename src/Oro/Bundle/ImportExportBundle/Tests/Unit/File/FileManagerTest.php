<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FileManagerTest extends TestCase
{
    use TempDirExtension;

    private GaufretteFileManager $gaufretteFileManager;
    private FileManager $fileManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->gaufretteFileManager = $this->createMock(GaufretteFileManager::class);
        $this->fileManager = new FileManager($this->gaufretteFileManager);
    }

    public function testGetMimeTypeFromString(): void
    {
        $fileName = 'file.png';
        $mimeType = 'image/png';

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($fileName)
            ->willReturn(true);
        $this->gaufretteFileManager->expects(self::once())
            ->method('getFileMimeType')
            ->with($fileName)
            ->willReturn($mimeType);

        self::assertEquals($mimeType, $this->fileManager->getMimeType($fileName));
    }

    public function testGetMimeTypeFromFileObject(): void
    {
        $fileName = 'file.png';
        $mimeType = 'image/png';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn($fileName);

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($fileName)
            ->willReturn(true);
        $this->gaufretteFileManager->expects(self::once())
            ->method('getFileMimeType')
            ->with($fileName)
            ->willReturn($mimeType);

        self::assertEquals($mimeType, $this->fileManager->getMimeType($file));
    }

    public function testGetMimeTypeOfNotExistedFile(): void
    {
        $fileName = 'file.png';

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($fileName)
            ->willReturn(false);

        self::assertNull($this->fileManager->getMimeType($fileName));
    }

    public function testGetMimeTypeFromStringWhenGaufretteAdapterDoesNotSupportMimeTypes(): void
    {
        $fileName = 'file.png';

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($fileName)
            ->willReturn(true);
        $this->gaufretteFileManager->expects(self::once())
            ->method('getFileMimeType')
            ->with($fileName)
            ->willReturn(null);

        self::assertNull($this->fileManager->getMimeType($fileName));
    }

    /**
     * @dataProvider fileContentDataProvider
     */
    public function testSaveFileToStorage(string $fileContent, string $expectedContent): void
    {
        $fileObject = $this->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['php://memory'])
            ->getMock();
        $fileObject->expects(self::once())
            ->method('fread')
            ->willReturn($fileContent);

        $fileInfo = $this->getMockBuilder(\SplFileInfo::class)
            ->setConstructorArgs(['testFileName'])
            ->getMock();
        $fileInfo->expects(self::once())
            ->method('openFile')
            ->willReturn($fileObject);
        $fileInfo->expects(self::once())
            ->method('getSize')
            ->willReturn(1);

        $this->gaufretteFileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($expectedContent, 'fileNameForSave');

        $this->fileManager->saveFileToStorage($fileInfo, 'fileNameForSave');
    }

    /**
     * @dataProvider fileContentDataProvider
     */
    public function testWriteFileToStorage(string $fileContent, string $expectedContent): void
    {
        $tmpFileName = $this->getTempFile('import_export_file_manager');

        file_put_contents($tmpFileName, $fileContent);

        $this->gaufretteFileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($expectedContent, 'fileNameForSave');

        $this->fileManager->writeFileToStorage($tmpFileName, 'fileNameForSave');
    }

    public function testCopyFileToStorage(): void
    {
        $bom = pack('H*', 'EFBBBF');
        $tmpFileName = $this->getTempFile('import_export_file');
        $tmpDirectory = $this->getTempDir('import_export_files');
        file_put_contents($tmpFileName, sprintf('%s%s', $bom, 'Some content'));

        $this->gaufretteFileManager = new GaufretteFileManager('import_export_files');
        $this->gaufretteFileManager->setProtocol('gaufrette');
        $this->gaufretteFileManager->setFilesystemMap(new FilesystemMap([
            'import_export_files' => new Filesystem(new LocalAdapter($tmpDirectory)),
        ]));

        $this->fileManager = new FileManager($this->gaufretteFileManager);
        $this->fileManager->copyFileToStorage($tmpFileName, 'import_export_file_without_bom');

        $result = $this->gaufretteFileManager->getFile('import_export_file_without_bom');
        self::assertFalse(str_starts_with($result->getContent(), $bom));
    }

    public function fileContentDataProvider(): array
    {
        $bomBytes = pack('H*', 'EFBBBF');

        return [
            [$bomBytes . 'Col1,Col2,Col3\nVal1Val2Val3', 'Col1,Col2,Col3\nVal1Val2Val3'],
            [$bomBytes . ' some Test Content ', ' some Test Content '],
            ['Test content ' . $bomBytes, 'Test content ' . $bomBytes],
        ];
    }

    public function testWriteToStorage(): void
    {
        $fileName = 'test.txt';
        $content = 'test content';

        $this->gaufretteFileManager->expects(self::once())
            ->method('writeToStorage')
            ->with($content, $fileName);

        $this->fileManager->writeToStorage($content, $fileName);
    }

    public function testWriteToTmpLocalStorage(): void
    {
        $fileName = 'test.txt';
        $content = 'test content';

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFileContent')
            ->with($fileName)
            ->willReturn($content);

        $fileManager = new FileManager($this->gaufretteFileManager);
        $tmpFileName = $fileManager->writeToTmpLocalStorage($fileName);
        self::assertFileExists($tmpFileName);
        self::assertEquals($content, file_get_contents($tmpFileName));

        self::assertCount(1, ReflectionUtil::getPropertyValue($fileManager, 'tempFileHandles'));

        // test that temp file is removed in destructor
        $fileManager = null;
        self::assertFileDoesNotExist($tmpFileName);
    }

    public function testWriteToTmpLocalStorageWhenTempFileIsRemovedOutside(): void
    {
        $fileName = 'test.txt';
        $content = 'test content';

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFileContent')
            ->with($fileName)
            ->willReturn($content);

        $fileManager = new FileManager($this->gaufretteFileManager);
        $tmpFileName = $fileManager->writeToTmpLocalStorage($fileName);
        self::assertFileExists($tmpFileName);
        self::assertEquals($content, file_get_contents($tmpFileName));

        $tempFileHandles = ReflectionUtil::getPropertyValue($fileManager, 'tempFileHandles');
        self::assertCount(1, $tempFileHandles);

        unlink(stream_get_meta_data($tempFileHandles[0])['uri']);
        self::assertFileDoesNotExist($tmpFileName);

        // test that there is no an exception in destructor
        $fileManager = null;
    }

    public function testWriteToTmpLocalStorageWhenTempFileIsClosedOutside(): void
    {
        $fileName = 'test.txt';
        $content = 'test content';

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFileContent')
            ->with($fileName)
            ->willReturn($content);

        $fileManager = new FileManager($this->gaufretteFileManager);
        $tmpFileName = $fileManager->writeToTmpLocalStorage($fileName);
        self::assertFileExists($tmpFileName);
        self::assertEquals($content, file_get_contents($tmpFileName));

        $tempFileHandles = ReflectionUtil::getPropertyValue($fileManager, 'tempFileHandles');
        self::assertCount(1, $tempFileHandles);

        fclose($tempFileHandles[0]);
        self::assertFileDoesNotExist($tmpFileName);

        // test that there is no an exception in destructor
        $fileManager = null;
    }

    public function testCreateTmpFile(): void
    {
        $fileManager = new FileManager($this->gaufretteFileManager);
        $tmpFileName = $fileManager->createTmpFile();
        self::assertFileExists($tmpFileName);

        $tempFileHandles = ReflectionUtil::getPropertyValue($fileManager, 'tempFileHandles');
        self::assertCount(1, $tempFileHandles);

        // test that temp file is removed in destructor
        $fileManager = null;
        self::assertFileDoesNotExist($tmpFileName);
    }

    public function testDeleteTmpFile(): void
    {
        $tmpFileName = $this->fileManager->createTmpFile();
        self::assertFileExists($tmpFileName);

        $tempFileHandles = ReflectionUtil::getPropertyValue($this->fileManager, 'tempFileHandles');
        self::assertCount(1, $tempFileHandles);

        $this->fileManager->deleteTmpFile($tmpFileName);
        self::assertFileDoesNotExist($tmpFileName);

        $tempFileHandles = ReflectionUtil::getPropertyValue($this->fileManager, 'tempFileHandles');
        self::assertCount(0, $tempFileHandles);
    }

    public function testDeleteTmpFileWhenTempFileIsClosedOutside(): void
    {
        $tmpFileName = $this->fileManager->createTmpFile();
        self::assertFileExists($tmpFileName);

        $tempFileHandles = ReflectionUtil::getPropertyValue($this->fileManager, 'tempFileHandles');
        self::assertCount(1, $tempFileHandles);

        fclose($tempFileHandles[0]);
        self::assertFileDoesNotExist($tmpFileName);

        $this->fileManager->deleteTmpFile($tmpFileName);
        self::assertFileDoesNotExist($tmpFileName);

        $tempFileHandles = ReflectionUtil::getPropertyValue($this->fileManager, 'tempFileHandles');
        self::assertCount(1, $tempFileHandles);
    }

    public function testGetFilesByPeriodWithDirectory(): void
    {
        $this->gaufretteFileManager->expects(self::once())
            ->method('findFiles')
            ->willReturn(['firstDirectory']);
        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with('firstDirectory')
            ->willReturn(false);

        self::assertEquals([], $this->fileManager->getFilesByPeriod());
    }

    public function testGetFilesByPeriodWithFile(): void
    {
        $this->gaufretteFileManager->expects(self::once())
            ->method('findFiles')
            ->willReturn(['someFile']);
        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with('someFile')
            ->willReturn(true);

        $someFile = $this->createMock(File::class);
        $someFile->expects(self::once())
            ->method('getMtime')
            ->willReturn(mktime(0, 0, 0, 12, 31, 2010));

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFile')
            ->willReturn($someFile);

        self::assertEquals(['someFile' => $someFile], $this->fileManager->getFilesByPeriod());
    }

    /**
     * @dataProvider getFilesByPeriodDataProvider
     */
    public function testGetFilesByPeriod(?\DateTime $from, ?\DateTime $to, array $expectedFiles): void
    {
        $this->gaufretteFileManager->expects(self::once())
            ->method('findFiles')
            ->willReturn(['firstFile', 'secondFile']);
        $this->gaufretteFileManager->expects(self::exactly(2))
            ->method('hasFile')
            ->withConsecutive(['firstFile'], ['secondFile'])
            ->willReturn(true);

        $firstFile = $this->createMock(File::class);
        $firstFile->expects(self::once())
            ->method('getMtime')
            ->willReturn(mktime(0, 0, 0, 12, 31, 2010));

        $secondFile = $this->createMock(File::class);
        $secondFile->expects(self::once())
            ->method('getMtime')
            ->willReturn(mktime(0, 0, 0, 12, 31, 2011));

        $this->gaufretteFileManager->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
               ['firstFile', true, $firstFile],
               ['secondFile', true, $secondFile]
            ]);

        self::assertEquals($expectedFiles, array_keys($this->fileManager->getFilesByPeriod($from, $to)));
    }

    public function getFilesByPeriodDataProvider(): array
    {
        return [
            'no limits' => [
                'from' => null,
                'to' => null,
                'expectedFiles' => ['firstFile', 'secondFile']
            ],
            'from limit applied' => [
                'from' => new \DateTime('2011-01-01'),
                'to' => null,
                'expectedFiles' => ['secondFile']
            ],
            'to limit applied' => [
                'from' => null,
                'to' => new \DateTime('2011-01-01'),
                'expectedFiles' => ['firstFile']
            ],
            'from and to limit applied' => [
                'from' => new \DateTime('2011-12-31'),
                'to' => new \DateTime('2012-01-01'),
                'expectedFiles' => ['secondFile']
            ],
        ];
    }

    public function testGetFilePath(): void
    {
        $fileName = 'test.txt';
        $filePath = 'gaufrette://file_system/sub_dir/test.txt';

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFilePath')
            ->with($fileName)
            ->willReturn($filePath);

        self::assertEquals($filePath, $this->fileManager->getFilePath($fileName));
    }

    public function testGetContent(): void
    {
        $fileName = 'test.txt';
        $fileContent = 'test content';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFile')
            ->with($fileName)
            ->willReturn($file);

        self::assertEquals($fileContent, $this->fileManager->getContent($fileName));
    }

    public function testGetContentFromFileObject(): void
    {
        $fileContent = 'test content';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);

        $this->gaufretteFileManager->expects(self::never())
            ->method('getFile');

        self::assertEquals($fileContent, $this->fileManager->getContent($file));
    }

    public function testDeleteFile(): void
    {
        $fileName = 'test.txt';

        $this->gaufretteFileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);

        $this->fileManager->deleteFile($fileName);
    }

    public function testDeleteFileByFileObject(): void
    {
        $fileName = 'test.txt';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn($fileName);

        $this->gaufretteFileManager->expects(self::once())
            ->method('deleteFile')
            ->with($fileName);

        $this->fileManager->deleteFile($file);
    }

    public function testDeleteFileByFileObjectWhenNoFileName(): void
    {
        $fileName = 'test.txt';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(null);

        $this->gaufretteFileManager->expects(self::never())
            ->method('deleteFile')
            ->with($fileName);

        $this->fileManager->deleteFile($file);
    }

    public function testisFileExistForExistingFile(): void
    {
        $fileName = 'test.txt';

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($fileName)
            ->willReturn(true);

        self::assertTrue($this->fileManager->isFileExist($fileName));
    }

    public function testisFileExistForNotExistingFile(): void
    {
        $fileName = 'test.txt';

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($fileName)
            ->willReturn(false);

        self::assertFalse($this->fileManager->isFileExist($fileName));
    }

    public function testFileSizeWhichNotExist(): void
    {
        $fileName = 'test.txt';
        $filePath = '/tmp/test.txt';

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFilePath')
            ->with($fileName)
            ->willReturn($filePath);

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($filePath)
            ->willReturn(false);

        self::assertEquals(0, $this->fileManager->getFileSize($fileName));
    }

    /**
     * @dataProvider filesDataProvider
     */
    public function testFileSizeWhichExist(string $content, int $expectedSize): void
    {
        $fileName = 'test.txt';
        $filePath = '/tmp/test.txt';

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFilePath')
            ->with($fileName)
            ->willReturn($filePath);

        $this->gaufretteFileManager->expects(self::once())
            ->method('hasFile')
            ->with($filePath)
            ->willReturn(true);

        file_put_contents($filePath, $content);
        try {
            self::assertFileExists($filePath);
            self::assertEquals($expectedSize, $this->fileManager->getFileSize($fileName));
        } finally {
            unlink($filePath);
        }
    }

    public function filesDataProvider(): array
    {
        return [
            ['Some Content', 12],
            ['', 0]
        ];
    }
}
