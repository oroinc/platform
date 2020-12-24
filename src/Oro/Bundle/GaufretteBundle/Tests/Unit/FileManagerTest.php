<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit;

use Gaufrette\Exception\FileNotFound as GaufretteFileNotFoundException;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Stream;
use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\Stream\Local as LocalStream;
use Gaufrette\StreamMode;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\GaufretteBundle\Exception\FlushFailedException;
use Oro\Bundle\GaufretteBundle\Exception\ProtocolConfigurationException;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FileManagerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_FILE_SYSTEM_NAME = 'testFileSystem';
    private const TEST_PROTOCOL         = 'testProtocol';

    /** @var Filesystem|\PHPUnit\Framework\MockObject\MockObject */
    private $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
    }

    /**
     * @param bool        $useSubDirectory
     * @param string|null $subDirectory
     *
     * @return FileManager
     */
    private function getFileManager(bool $useSubDirectory, string $subDirectory = null): FileManager
    {
        $fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME, $subDirectory);
        $fileManager->setProtocol(self::TEST_PROTOCOL);
        if ($useSubDirectory) {
            $fileManager->useSubDirectory(true);
        }

        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn($this->filesystem);
        $fileManager->setFilesystemMap($filesystemMap);

        return $fileManager;
    }

    public function testGetProtocol()
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(self::TEST_PROTOCOL, $fileManager->getProtocol());
    }

    public function testGetFilePath()
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/test.txt',
                self::TEST_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getFilePath('test.txt')
        );
    }

    public function testGetFilePathForNotSubDirectoryAwareFileManager()
    {
        $fileManager = $this->getFileManager(false);

        self::assertEquals(
            sprintf(
                '%s://%s/test.txt',
                self::TEST_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getFilePath('test.txt')
        );
    }

    public function testGetFilePathWithCustomSubDirectory()
    {
        $fileManager = $this->getFileManager(true, 'testSubDir');
        $fileManager->setProtocol(self::TEST_PROTOCOL);

        self::assertEquals(
            sprintf('%s://%s/%s/test.txt', self::TEST_PROTOCOL, self::TEST_FILE_SYSTEM_NAME, 'testSubDir'),
            $fileManager->getFilePath('test.txt')
        );
    }

    public function testGetFilePathWithAutoConfiguredCustomSubDirectory()
    {
        $fileManager = $this->getFileManager(false, 'testSubDir');
        $fileManager->setProtocol(self::TEST_PROTOCOL);

        self::assertEquals(
            sprintf('%s://%s/%s/test.txt', self::TEST_PROTOCOL, self::TEST_FILE_SYSTEM_NAME, 'testSubDir'),
            $fileManager->getFilePath('test.txt')
        );
    }

    public function testGetFilePathWhenProtocolIsNotConfigured()
    {
        $this->expectException(ProtocolConfigurationException::class);

        $fileManager = $this->getFileManager(true);
        $fileManager->setProtocol('');
        $fileManager->getFilePath('test.txt');
    }

    public function testGetFilePathWhenFileNameHaveLeadingSlash()
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/path/test.txt',
                self::TEST_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getFilePath('/path/test.txt')
        );
    }

    public function testFindAllFiles()
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/')
            ->willReturn([
                'keys' => [self::TEST_FILE_SYSTEM_NAME . '/file1', self::TEST_FILE_SYSTEM_NAME . '/file2'],
                'dirs' => ['dir1']
            ]);

        $fileManager = $this->getFileManager(true);

        self::assertEquals(['file1', 'file2'], $fileManager->findFiles());
    }

    public function testFindFilesByPrefix()
    {
        $prefix = 'prefix';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/' . $prefix;

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with($directory)
            ->willReturn([
                'keys' => [$directory . '_file1', $directory . '_file2'],
                'dirs' => ['dir1']
            ]);

        $fileManager = $this->getFileManager(true);

        self::assertEquals([$prefix . '_file1', $prefix . '_file2'], $fileManager->findFiles($prefix));
    }

    public function testFindFilesWhenNoFilesFound()
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/prefix')
            ->willReturn([]);

        $fileManager = $this->getFileManager(true);

        self::assertSame([], $fileManager->findFiles('prefix'));
    }

    /**
     * E.g. this may happens when AwsS3 or GoogleCloudStorage adapters are used
     */
    public function testFindFilesWhenAdapterReturnsOnlyKeys()
    {
        $prefix = 'prefix';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/' . $prefix;

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with($directory)
            ->willReturn([$directory . '_file1', $directory . '_file2']);

        $fileManager = $this->getFileManager(true);

        self::assertEquals([$prefix . '_file1', $prefix . '_file2'], $fileManager->findFiles($prefix));
    }

    public function testFindAllFilesForNotSubDirectoryAwareFileManager()
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('')
            ->willReturn([
                'keys' => ['file1', 'file2'],
                'dirs' => ['dir1']
            ]);

        $fileManager = $this->getFileManager(false);

        self::assertEquals(['file1', 'file2'], $fileManager->findFiles());
    }

    public function testFindFilesByPrefixForNotSubDirectoryAwareFileManager()
    {
        $prefix = 'prefix';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with($prefix)
            ->willReturn([
                'keys' => [$prefix . '_file1', $prefix . '_file2'],
                'dirs' => ['dir1']
            ]);

        $fileManager = $this->getFileManager(false);

        self::assertEquals([$prefix . '_file1', $prefix . '_file2'], $fileManager->findFiles($prefix));
    }

    public function testHasFileWhenFileExists()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);

        $fileManager = $this->getFileManager(true);

        self::assertTrue($fileManager->hasFile($fileName));
    }

    public function testHasFileWhenFileDoesNotExist()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);

        $fileManager = $this->getFileManager(true);

        self::assertFalse($fileManager->hasFile($fileName));
    }

    public function testHasFileForNotSubDirectoryAwareFileManager()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);

        $fileManager = $this->getFileManager(false);

        self::assertTrue($fileManager->hasFile($fileName));
    }

    public function testGetFileByFileName()
    {
        $fileName = 'testFile.txt';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $file->expects(self::once())
            ->method('setName')
            ->with($fileName);

        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($file);

        $fileManager = $this->getFileManager(true);

        self::assertSame($file, $fileManager->getFile($fileName));
    }

    public function testGetFileWhenFileDoesNotExistAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('get');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFile($fileName, false));
    }

    public function testGetFileWhenFileExistsAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $file->expects(self::once())
            ->method('setName')
            ->with($fileName);

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($file);

        $fileManager = $this->getFileManager(true);

        self::assertSame($file, $fileManager->getFile($fileName, false));
    }

    public function testGetFileForNotSubDirectoryAwareFileManager()
    {
        $fileName = 'testFile.txt';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn($fileName);
        $file->expects(self::once())
            ->method('setName')
            ->with($fileName);

        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with($fileName)
            ->willReturn($file);

        $fileManager = $this->getFileManager(false);

        self::assertSame($file, $fileManager->getFile($fileName));
    }

    public function testGetStreamWhenFileDoesNotExist()
    {
        $this->expectException(GaufretteFileNotFoundException::class);
        $this->expectExceptionMessage('The file "testFileSystem/testFile.txt" was not found.');

        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('createStream');

        $fileManager = $this->getFileManager(true);

        $fileManager->getStream($fileName);
    }

    public function testGetStreamWhenFileDoesNotExistAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('createStream');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getStream($fileName, false));
    }

    public function testGetStreamWhenFileExistsAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';
        $stream = new LocalStream('test');

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($stream);

        $fileManager = $this->getFileManager(true);

        self::assertSame($stream, $fileManager->getStream($fileName, false));
    }

    public function testGetStreamForNotSubDirectoryAwareFileManager()
    {
        $fileName = 'testFile.txt';
        $stream = new LocalStream('test');

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($stream);

        $fileManager = $this->getFileManager(false);

        self::assertSame($stream, $fileManager->getStream($fileName, false));
    }

    public function testGetFileContentByFileName()
    {
        $fileName = 'testFile.txt';
        $fileContent = 'test data';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $file->expects(self::once())
            ->method('setName')
            ->with($fileName);

        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($file);

        $fileManager = $this->getFileManager(true);

        self::assertEquals($fileContent, $fileManager->getFileContent($fileName));
    }

    public function testGetFileContentWhenFileDoesNotExistAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('get');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFileContent($fileName, false));
    }

    public function testGetFileContentWhenFileExistsAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';
        $fileContent = 'test data';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $file->expects(self::once())
            ->method('setName')
            ->with($fileName);

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($file);

        $fileManager = $this->getFileManager(true);

        self::assertEquals($fileContent, $fileManager->getFileContent($fileName, false));
    }

    public function testGetFileContentForNotSubDirectoryAwareFileManager()
    {
        $fileName = 'testFile.txt';
        $fileContent = 'test data';

        $file = $this->createMock(File::class);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn($fileName);
        $file->expects(self::once())
            ->method('setName')
            ->with($fileName);

        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with($fileName)
            ->willReturn($file);

        $fileManager = $this->getFileManager(false);

        self::assertEquals($fileContent, $fileManager->getFileContent($fileName));
    }

    public function testDeleteFile()
    {
        $fileName = 'text.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileForNotExistingFile()
    {
        $fileName = 'text.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileWhenFileNameIsEmpty()
    {
        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile('');
    }

    public function testDeleteFileForNotSubDirectoryAwareFileManager()
    {
        $fileName = 'text.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with($fileName);

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteAllFiles()
    {
        $fileNames = [self::TEST_FILE_SYSTEM_NAME . '/text1.txt', self::TEST_FILE_SYSTEM_NAME . '/text2.txt'];

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->willReturn([
                'keys' => $fileNames,
                'dirs' => ['dir1']
            ]);
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$fileNames[0]],
                [$fileNames[1]]
            );

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles();
    }

    public function testDeleteAllFilesForNotSubDirectoryAwareFileManager()
    {
        $fileNames = ['text1.txt', 'text2.txt'];

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->willReturn([
                'keys' => $fileNames,
                'dirs' => ['dir1']
            ]);
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$fileNames[0]],
                [$fileNames[1]]
            );

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteAllFiles();
    }

    public function testWriteToStorage()
    {
        $content = 'Test data';
        $fileName = 'test2.txt';

        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        $fileManager->writeToStorage($content, $fileName);

        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);
        self::assertEquals($content, $resultStream->read(100));
    }

    public function testWriteToStorageWhenFlushFailed()
    {
        $this->expectException(FlushFailedException::class);
        $this->expectExceptionMessage('Failed to flush data to the "test2.txt" file.');

        $content = 'Test data';
        $fileName = 'test2.txt';

        $resultStream = $this->createMock(Stream::class);
        $resultStream->expects(self::once())
            ->method('open')
            ->with(new StreamMode('wb+'));
        $resultStream->expects(self::once())
            ->method('write')
            ->with($content);
        $resultStream->expects(self::once())
            ->method('flush')
            ->willReturn(false);
        $resultStream->expects(self::once())
            ->method('close');

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        $fileManager->writeToStorage($content, $fileName);
    }

    public function testWriteToStorageForNotSubDirectoryAwareFileManager()
    {
        $content = 'Test data';
        $fileName = 'test2.txt';

        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with($fileName);

        $fileManager = $this->getFileManager(false);

        $fileManager->writeToStorage($content, $fileName);

        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);
        self::assertEquals($content, $resultStream->read(100));
    }

    public function testWriteFileToStorage()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        $fileManager->writeFileToStorage($localFilePath, $fileName);

        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);
        self::assertStringEqualsFile($localFilePath, $resultStream->read(100));
    }

    public function testWriteFileToStorageForNotSubDirectoryAwareFileManager()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with($fileName);

        $fileManager = $this->getFileManager(false);

        $fileManager->writeFileToStorage($localFilePath, $fileName);

        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);
        self::assertStringEqualsFile($localFilePath, $resultStream->read(100));
    }

    public function testWriteStreamToStorage()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        $result = $fileManager->writeStreamToStorage($srcStream, $fileName);

        $resultStream->open(new StreamMode('rb'));
        $resultStream->seek(0);
        self::assertStringEqualsFile($localFilePath, $resultStream->read(100));
        self::assertTrue($result);
        // test that the input stream is closed
        self::assertFalse($srcStream->cast(1));
    }

    public function testWriteStreamToStorageWhenFlushFailed()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = $this->createMock(Stream::class);
        $resultStream->expects(self::once())
            ->method('open')
            ->with(new StreamMode('wb+'));
        $resultStream->expects(self::once())
            ->method('write')
            ->with(file_get_contents($localFilePath));
        $resultStream->expects(self::once())
            ->method('flush')
            ->willReturn(false);
        $resultStream->expects(self::once())
            ->method('close');

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        try {
            $fileManager->writeStreamToStorage($srcStream, $fileName);
            self::fail('Expected FlushFailedException');
        } catch (FlushFailedException $e) {
            self::assertEquals('Failed to flush data to the "test2.txt" file.', $e->getMessage());
            // test that the input stream is closed
            self::assertFalse($srcStream->cast(1));
        }
    }

    public function testWriteStreamToStorageWithEmptyStreamAndAvoidWriteEmptyStream()
    {
        $localFilePath = __DIR__ . '/Fixtures/emptyFile.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);

        $this->filesystem->expects(self::never())
            ->method('createStream')
            ->with($fileName);
        $this->filesystem->expects(self::never())
            ->method('removeFromRegister')
            ->with($fileName);

        $fileManager = $this->getFileManager(true);

        $result = $fileManager->writeStreamToStorage($srcStream, $fileName, true);

        self::assertFalse($result);
        // test that the input stream is closed
        self::assertFalse($srcStream->cast(1));
    }

    public function testWriteStreamToStorageWithEmptyStream()
    {
        $localFilePath = __DIR__ . '/Fixtures/emptyFile.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        $result = $fileManager->writeStreamToStorage($srcStream, $fileName);

        $resultStream->open(new StreamMode('rb'));
        $resultStream->seek(0);
        self::assertEmpty($resultStream->read(100));
        self::assertTrue($result);
        // test that the input stream is closed
        self::assertFalse($srcStream->cast(1));
    }

    public function testWriteStreamToStorageAndAvoidWriteEmptyStream()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);

        $fileManager = $this->getFileManager(true);

        $result = $fileManager->writeStreamToStorage($srcStream, $fileName, true);

        $resultStream->open(new StreamMode('rb'));
        $resultStream->seek(0);
        self::assertStringEqualsFile($localFilePath, $resultStream->read(100));
        self::assertTrue($result);
        // test that the input stream is closed
        self::assertFalse($srcStream->cast(1));
    }

    public function testWriteStreamToStorageForNotSubDirectoryAwareFileManager()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with($fileName);

        $fileManager = $this->getFileManager(false);

        $result = $fileManager->writeStreamToStorage($srcStream, $fileName);

        $resultStream->open(new StreamMode('rb'));
        $resultStream->seek(0);
        self::assertStringEqualsFile($localFilePath, $resultStream->read(100));
        self::assertTrue($result);
        // test that the input stream is closed
        self::assertFalse($srcStream->cast(1));
    }

    public function testWriteToTemporaryFile()
    {
        $content = 'Test data';

        $fileManager = $this->getFileManager(true);

        $resultFile = null;
        try {
            $resultFile = $fileManager->writeToTemporaryFile($content);
            try {
                self::assertEquals($content, file_get_contents($resultFile->getRealPath()));
            } finally {
                @unlink($resultFile->getRealPath());
            }
        } catch (IOException $e) {
            // no access to temporary file - ignore this error
        }
    }

    public function testWriteStreamToTemporaryFile()
    {
        $content = 'Test data';

        $srcStream = new InMemoryBuffer($this->filesystem, 'test.txt');
        $srcStream->open(new StreamMode('wb+'));
        $srcStream->write($content);
        $srcStream->seek(0);
        $srcStream->close();

        $fileManager = $this->getFileManager(true);

        $resultFile = null;
        try {
            $resultFile = $fileManager->writeStreamToTemporaryFile($srcStream);
            try {
                self::assertEquals($content, file_get_contents($resultFile->getRealPath()));
            } finally {
                @unlink($resultFile->getRealPath());
            }
        } catch (\RuntimeException $e) {
            if (false === strpos($e->getMessage(), 'cannot be opened')) {
                throw $e;
            }
            /**
             * cannot open temporary file - ignore this error
             * @see \Gaufrette\Stream\Local::open
             */
        }
    }

    public function testGetTemporaryFileNameWithoutSuggestedFileName()
    {
        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName();
        self::assertNotEmpty($tmpFileName);

        $parts = explode(DIRECTORY_SEPARATOR, $tmpFileName);
        if (0 === strpos($tmpFileName, DIRECTORY_SEPARATOR)) {
            array_shift($parts);
        }
        foreach ($parts as $part) {
            self::assertNotEmpty(
                $part,
                sprintf('Several directory separators follow each other. File Name: %s', $tmpFileName)
            );
        }
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithoutExtension()
    {
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true)));

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);

        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $suggestedFileName, $tmpFileName);
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithExtension()
    {
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true))) . '.txt';

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);

        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $suggestedFileName, $tmpFileName);
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithoutExtensionWhenFileAlreadyExists()
    {
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true)));

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);
        try {
            if (false !== @file_put_contents($tmpFileName, 'test')) {
                // guard
                self::assertFileExists($tmpFileName, 'guard');

                $anotherTmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);
                self::assertNotEmpty($anotherTmpFileName);
                self::assertNotEquals($tmpFileName, $anotherTmpFileName);
                self::assertFileDoesNotExist($anotherTmpFileName);
            }
        } finally {
            @unlink($tmpFileName);
        }
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithExtensionWhenFileAlreadyExists()
    {
        $fileExtension = '.txt';
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true))) . $fileExtension;

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);
        try {
            if (false !== @file_put_contents($tmpFileName, 'test')) {
                // guard
                self::assertFileExists($tmpFileName, 'guard');

                $anotherTmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);
                self::assertNotEmpty($anotherTmpFileName);
                self::assertNotEquals($tmpFileName, $anotherTmpFileName);
                self::assertStringEndsWith($fileExtension, $anotherTmpFileName);
                self::assertFileDoesNotExist($anotherTmpFileName);
            }
        } finally {
            @unlink($tmpFileName);
        }
    }

    public function testGetSubDirectoryWithoutCustomSubDirectory()
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(self::TEST_FILE_SYSTEM_NAME, $fileManager->getSubDirectory());
    }

    public function testGetSubDirectoryWithCustomSubDirectory()
    {
        $subDirectory = 'testDir';

        $fileManager = $this->getFileManager(true, $subDirectory);

        self::assertEquals($subDirectory, $fileManager->getSubDirectory());
    }

    public function testGetSubDirectoryForNotSubDirectoryAwareFileManager()
    {
        $fileManager = $this->getFileManager(false);

        self::assertNull($fileManager->getSubDirectory());
    }

    public function testGetFileMimeType()
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file(__DIR__ . '/Fixtures/test.txt');

        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/test_file.txt')
            ->willReturn($mimeType);

        $fileManager = $this->getFileManager(true);

        self::assertEquals($mimeType, $fileManager->getFileMimeType('test_file.txt'));
    }

    public function testGetFileMimeTypeWhenGaufretteAdapterCannotRecognizeMimeType()
    {
        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/test_file.txt')
            ->willReturn('');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFileMimeType('test_file.txt'));
    }

    public function testGetFileMimeTypeWhenGaufretteAdapterDoesNotSupportMimeTypes()
    {
        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/test_file.txt')
            ->willThrowException(new \LogicException());

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFileMimeType('test_file.txt'));
    }

    public function testGetFileMimeTypeForNotSubDirectoryAwareFileManager()
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file(__DIR__ . '/Fixtures/test.txt');

        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with('test_file.txt')
            ->willReturn($mimeType);

        $fileManager = $this->getFileManager(false);

        self::assertEquals($mimeType, $fileManager->getFileMimeType('test_file.txt'));
    }
}
