<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit;

use Gaufrette\Adapter;
use Gaufrette\Adapter\InMemory;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Stream;
use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\Stream\Local as LocalStream;
use Gaufrette\StreamMode;
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Oro\Bundle\GaufretteBundle\Exception\FlushFailedException;
use Oro\Bundle\GaufretteBundle\Exception\ProtocolConfigurationException;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FileManagerTest extends TestCase
{
    private const TEST_FILE_SYSTEM_NAME = 'testFileSystem';
    private const TEST_PROTOCOL = 'testProtocol';
    private const TEST_READONLY_PROTOCOL = 'testReadonlyProtocol';

    private Filesystem&MockObject $filesystem;
    private Adapter&MockObject $filesystemAdapter;

    #[\Override]
    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->filesystemAdapter = $this->createMock(Adapter::class);
        $this->filesystem->expects(self::any())
            ->method('getAdapter')
            ->willReturn($this->filesystemAdapter);
    }

    private function getFileManager(bool $useSubDirectory, ?string $subDirectory = null): FileManager
    {
        $fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME, $subDirectory);
        $fileManager->setProtocol(self::TEST_PROTOCOL);
        $fileManager->setReadonlyProtocol(self::TEST_READONLY_PROTOCOL);
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

    public function testGetProtocol(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(self::TEST_PROTOCOL, $fileManager->getProtocol());
    }

    public function testGetReadonlyProtocol(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(self::TEST_READONLY_PROTOCOL, $fileManager->getReadonlyProtocol());
    }

    public function testGetSubDirectory(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(self::TEST_FILE_SYSTEM_NAME, $fileManager->getSubDirectory());
    }

    public function testGetSubDirectoryForNotSubDirAwareManager(): void
    {
        $fileManager = $this->getFileManager(false);

        self::assertNull($fileManager->getSubDirectory());
    }

    public function testGetSubDirectoryWithCustomSubDirectory(): void
    {
        $subDirectory = 'testSubDir';

        $fileManager = $this->getFileManager(true, $subDirectory);

        self::assertEquals($subDirectory, $fileManager->getSubDirectory());
    }

    public function testGetSubDirectoryWithoutCustomSubDirectory(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(self::TEST_FILE_SYSTEM_NAME, $fileManager->getSubDirectory());
    }

    public function testGetSubDirectoryForNotSubDirectoryAwareFileManager(): void
    {
        $fileManager = $this->getFileManager(false);

        self::assertNull($fileManager->getSubDirectory());
    }

    public function testGetFilePath(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/file.txt',
                self::TEST_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getFilePath('file.txt')
        );
    }

    public function testGetFilePathForNotSubDirAwareManager(): void
    {
        $fileManager = $this->getFileManager(false);

        self::assertEquals(
            sprintf(
                '%s://%s/file.txt',
                self::TEST_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getFilePath('file.txt')
        );
    }

    public function testGetFilePathWhenFileNameIsEmptyString(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/',
                self::TEST_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getFilePath('')
        );
    }

    public function testGetFilePathWithCustomSubDirectory(): void
    {
        $fileManager = $this->getFileManager(true, 'testSubDir');

        self::assertEquals(
            sprintf('%s://%s/%s/file.txt', self::TEST_PROTOCOL, self::TEST_FILE_SYSTEM_NAME, 'testSubDir'),
            $fileManager->getFilePath('file.txt')
        );
    }

    public function testGetFilePathWithAutoConfiguredCustomSubDirectory(): void
    {
        $fileManager = $this->getFileManager(false, 'testSubDir');

        self::assertEquals(
            sprintf('%s://%s/%s/file.txt', self::TEST_PROTOCOL, self::TEST_FILE_SYSTEM_NAME, 'testSubDir'),
            $fileManager->getFilePath('file.txt')
        );
    }

    public function testGetFilePathWhenProtocolIsNotConfigured(): void
    {
        $this->expectException(ProtocolConfigurationException::class);

        $fileManager = $this->getFileManager(true);
        $fileManager->setProtocol('');
        $fileManager->getFilePath('file.txt');
    }

    public function testGetFilePathWhenFileNameHaveLeadingSlash(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/path/file.txt',
                self::TEST_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getFilePath('/path/file.txt')
        );
    }

    public function testGetReadonlyFilePath(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/file.txt',
                self::TEST_READONLY_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getReadonlyFilePath('file.txt')
        );
    }

    public function testGetReadonlyFilePathForNotSubDirAwareManager(): void
    {
        $fileManager = $this->getFileManager(false);

        self::assertEquals(
            sprintf(
                '%s://%s/file.txt',
                self::TEST_READONLY_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getReadonlyFilePath('file.txt')
        );
    }

    public function testGetReadonlyFilePathWhenFileNameIsEmptyString(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/',
                self::TEST_READONLY_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getReadonlyFilePath('')
        );
    }

    public function testGetReadonlyFilePathWithCustomSubDirectory(): void
    {
        $fileManager = $this->getFileManager(true, 'testSubDir');

        self::assertEquals(
            sprintf('%s://%s/%s/file.txt', self::TEST_READONLY_PROTOCOL, self::TEST_FILE_SYSTEM_NAME, 'testSubDir'),
            $fileManager->getReadonlyFilePath('file.txt')
        );
    }

    public function testGetReadonlyFilePathWithAutoConfiguredCustomSubDirectory(): void
    {
        $fileManager = $this->getFileManager(false, 'testSubDir');

        self::assertEquals(
            sprintf('%s://%s/%s/file.txt', self::TEST_READONLY_PROTOCOL, self::TEST_FILE_SYSTEM_NAME, 'testSubDir'),
            $fileManager->getReadonlyFilePath('file.txt')
        );
    }

    public function testGetReadonlyFilePathWhenProtocolIsNotConfigured(): void
    {
        $this->expectException(ProtocolConfigurationException::class);

        $fileManager = $this->getFileManager(true);
        $fileManager->setReadonlyProtocol('');
        $fileManager->getReadonlyFilePath('file.txt');
    }

    public function testGetReadonlyFilePathWhenFileNameHaveLeadingSlash(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            sprintf(
                '%s://%s/%s/path/file.txt',
                self::TEST_READONLY_PROTOCOL,
                self::TEST_FILE_SYSTEM_NAME,
                self::TEST_FILE_SYSTEM_NAME
            ),
            $fileManager->getReadonlyFilePath('/path/file.txt')
        );
    }

    public function testGetReadonlyFilePathWithoutProtocol(): void
    {
        $fileManager = $this->getFileManager(true);

        self::assertEquals(
            'testFileSystem/testFileSystem/file.txt',
            $fileManager->getFilePathWithoutProtocol('file.txt')
        );
    }

    public function testGetFilePathWithoutProtocolForNotSubDirAwareManager(): void
    {
        $fileManager = $this->getFileManager(false);

        self::assertEquals(
            'testFileSystem/file.txt',
            $fileManager->getFilePathWithoutProtocol('file.txt')
        );
    }

    public function testGetFilePathWithoutProtocolWithCustomSubDirectory(): void
    {
        $fileManager = $this->getFileManager(true, 'testSubDir');

        self::assertEquals(
            'testFileSystem/testSubDir/file.txt',
            $fileManager->getFilePathWithoutProtocol('file.txt')
        );
    }

    public function testGetAdapterDescription(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::exactly(2))
            ->method('getAdapter')
            ->willReturn(new InMemory());
        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn($filesystem);
        $fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME);
        $fileManager->setFilesystemMap($filesystemMap);

        self::assertEquals('InMemory', $fileManager->getAdapterDescription());
    }

    public function testGetAdapterDescriptionWithLocalAdapter(): void
    {
        $expected = __DIR__;
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())
            ->method('getAdapter')
            ->willReturn(new LocalAdapter($expected));
        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn($filesystem);
        $fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME);
        $fileManager->setFilesystemMap($filesystemMap);

        self::assertEquals($expected, $fileManager->getAdapterDescription());
    }

    public function testGetLocalPathWithNonLocalAdapter(): void
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())
            ->method('getAdapter')
            ->willReturn(new InMemory());
        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn($filesystem);
        $fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME);
        $fileManager->setFilesystemMap($filesystemMap);

        self::assertNull($fileManager->getLocalPath());
    }

    public function testGetLocalPathWithLocalAdapter(): void
    {
        $expected = __DIR__;
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects(self::once())
            ->method('getAdapter')
            ->willReturn(new LocalAdapter($expected));
        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn($filesystem);
        $fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME);
        $fileManager->setFilesystemMap($filesystemMap);

        self::assertEquals($expected, $fileManager->getLocalPath());
    }

    public function testGetFileMimeType(): void
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file(__DIR__ . '/Fixtures/test.txt');

        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/file.txt')
            ->willReturn($mimeType);

        $fileManager = $this->getFileManager(true);

        self::assertEquals($mimeType, $fileManager->getFileMimeType('file.txt'));
    }

    public function testGetFileMimeTypeForNotSubDirAwareManager(): void
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file(__DIR__ . '/Fixtures/test.txt');

        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with('file.txt')
            ->willReturn($mimeType);

        $fileManager = $this->getFileManager(false);

        self::assertEquals($mimeType, $fileManager->getFileMimeType('file.txt'));
    }

    public function testGetFileMimeTypeWhenFileNameIsEmptyString(): void
    {
        $this->filesystem->expects(self::never())
            ->method('mimeType');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFileMimeType(''));
    }

    public function testGetFileMimeTypeWhenGaufretteAdapterCannotRecognizeMimeType(): void
    {
        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/file.txt')
            ->willReturn('');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFileMimeType('file.txt'));
    }

    public function testGetFileMimeTypeWhenGaufretteAdapterDoesNotSupportMimeTypes(): void
    {
        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/file.txt')
            ->willThrowException(new \LogicException());

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFileMimeType('file.txt'));
    }

    public function testGetFileMimeTypeForNotSubDirectoryAwareFileManager(): void
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $fileInfo->file(__DIR__ . '/Fixtures/test.txt');

        $this->filesystem->expects(self::once())
            ->method('mimeType')
            ->with('file.txt')
            ->willReturn($mimeType);

        $fileManager = $this->getFileManager(false);

        self::assertEquals($mimeType, $fileManager->getFileMimeType('file.txt'));
    }

    public function testFindFiles(): void
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/')
            ->willReturn([
                'keys' => [self::TEST_FILE_SYSTEM_NAME . '/file1', self::TEST_FILE_SYSTEM_NAME . '/file2'],
                'dirs' => [self::TEST_FILE_SYSTEM_NAME . '/dir1']
            ]);

        $fileManager = $this->getFileManager(true);

        self::assertEquals(['file1', 'file2'], $fileManager->findFiles());
    }

    public function testFindFilesForNotSubDirAwareManager(): void
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

    public function testFindFilesByPrefix(): void
    {
        $prefix = 'prefix';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/' . $prefix;

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $prefix)
            ->willReturn([
                'keys' => [$directory . '_file1', $directory . '_file2'],
                'dirs' => [$directory . '/dir1']
            ]);

        $fileManager = $this->getFileManager(true);

        self::assertEquals([$prefix . '_file1', $prefix . '_file2'], $fileManager->findFiles($prefix));
    }

    public function testFindFilesByPrefixForNotSubDirAwareManager(): void
    {
        $prefix = 'prefix';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with($prefix)
            ->willReturn([
                'keys' => [$prefix . '_file1', $prefix . '_file2'],
                'dirs' => [$prefix . '/dir1']
            ]);

        $fileManager = $this->getFileManager(false);

        self::assertEquals([$prefix . '_file1', $prefix . '_file2'], $fileManager->findFiles($prefix));
    }

    public function testFindFilesByPrefixWhenPrefixIsSlash(): void
    {
        $prefix = '/';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/')
            ->willReturn([
                'keys' => [$directory . 'file1', $directory . 'file2'],
                'dirs' => [$directory . 'dir1']
            ]);

        $fileManager = $this->getFileManager(true);

        self::assertEquals(['file1', 'file2'], $fileManager->findFiles($prefix));
    }

    public function testFindFilesByPrefixWhenPrefixIsSlashForNotSubDirAwareManager(): void
    {
        $prefix = '/';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('')
            ->willReturn([
                'keys' => ['file1', 'file2'],
                'dirs' => ['dir1']
            ]);

        $fileManager = $this->getFileManager(false);

        self::assertEquals(['file1', 'file2'], $fileManager->findFiles($prefix));
    }

    public function testFindFilesWhenNoFilesFound(): void
    {
        $prefix = 'prefix';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $prefix)
            ->willReturn([]);

        $fileManager = $this->getFileManager(true);

        self::assertSame([], $fileManager->findFiles($prefix));
    }

    /**
     * E.g. this may happens when AwsS3 or GoogleCloudStorage adapters are used
     */
    public function testFindFilesWhenAdapterReturnsOnlyKeys(): void
    {
        $prefix = 'prefix';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/' . $prefix;

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $prefix)
            ->willReturn([$directory . '_file1', $directory . '_file2']);

        $fileManager = $this->getFileManager(true);

        self::assertEquals([$prefix . '_file1', $prefix . '_file2'], $fileManager->findFiles($prefix));
    }

    public function testHasFileWhenFileExists(): void
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);

        $fileManager = $this->getFileManager(true);

        self::assertTrue($fileManager->hasFile($fileName));
    }

    public function testHasFileWhenFileDoesNotExist(): void
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);

        $fileManager = $this->getFileManager(true);

        self::assertFalse($fileManager->hasFile($fileName));
    }

    public function testHasFileWhenFileNameIsEmptyString(): void
    {
        $this->filesystem->expects(self::never())
            ->method('has');

        $fileManager = $this->getFileManager(true);

        self::assertFalse($fileManager->hasFile(''));
    }

    public function testHasFileForNotSubDirAwareManager(): void
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);

        $fileManager = $this->getFileManager(false);

        self::assertTrue($fileManager->hasFile($fileName));
    }

    public function testGetFile(): void
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

    public function testGetFileForNotSubDirAwareManager(): void
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

    public function testGetFileWhenFileDoesNotExistAndRequestedIgnoreException(): void
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

    public function testGetFileWhenFileExistsAndRequestedIgnoreException(): void
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

    public function testGetFileWhenFileNameIsEmptyString(): void
    {
        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::never())
            ->method('get');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFile(''));
    }

    public function testGetStreamWhenFileDoesNotExist(): void
    {
        $this->expectException(FileNotFound::class);
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

    public function testGetStreamWhenFileDoesNotExistAndRequestedIgnoreException(): void
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

    public function testGetStreamWhenFileExistsAndRequestedIgnoreException(): void
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

    public function testGetStreamWhenFileNameIsEmptyString(): void
    {
        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::never())
            ->method('createStream');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getStream(''));
    }

    public function testGetStreamForNotSubDirAwareManager(): void
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

    public function testGetFileContent(): void
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

    public function testGetFileContentForNotSubDirAwareManager(): void
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

    public function testGetFileContentWhenFileDoesNotExistAndRequestedIgnoreException(): void
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

    public function testGetFileContentWhenFileExistsAndRequestedIgnoreException(): void
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

    public function testGetFileContentWhenFileNameIsEmptyString(): void
    {
        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::never())
            ->method('get');

        $fileManager = $this->getFileManager(true);

        self::assertNull($fileManager->getFileContent(''));
    }

    public function testDeleteFile(): void
    {
        $fileName = 'file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('isDirectory')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileForNotSubDirAwareManager(): void
    {
        $fileName = 'file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('isDirectory')
            ->with($fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with($fileName);
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileWhenFileNameIsDirectory(): void
    {
        $fileName = 'file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('isDirectory')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::never())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileForNotExistingFile(): void
    {
        $fileName = 'file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::never())
            ->method('delete');
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileWhenFileNameIsEmptyString(): void
    {
        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::never())
            ->method('delete');
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile('');
    }

    public function testDeleteFileFromSubDirAndThereAreOtherFiles(): void
    {
        $fileName = 'dir/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir/')
            ->willReturn(['keys' => [self::TEST_FILE_SYSTEM_NAME . '/dir/another_file.txt'], 'dirs' => []]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    /**
     * E.g. this may happens when AwsS3 or GoogleCloudStorage adapters are used
     */
    public function testDeleteFileFromSubDirAndThereAreOtherFilesWhenAdapterReturnsOnlyKeys(): void
    {
        $fileName = 'dir/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir/')
            ->willReturn([self::TEST_FILE_SYSTEM_NAME . '/dir/another_file.txt']);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromSubDirAndNoOtherFiles(): void
    {
        $fileName = 'dir/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir/')
            ->willReturn(['keys' => [], 'dirs' => []]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromSubDirAndNoOtherFilesForNotSubDirAwareManager(): void
    {
        $fileName = 'dir/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [$fileName],
                ['dir']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('dir/')
            ->willReturn(['keys' => [], 'dirs' => []]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with($fileName);
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with('dir');

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteFile($fileName);
    }

    /**
     * E.g. this may happens when AwsS3 or GoogleCloudStorage adapters are used
     */
    public function testDeleteFileFromSubDirAndNoOtherFilesWhenAdapterReturnsOnlyKeys(): void
    {
        $fileName = 'dir/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir/')
            ->willReturn([]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromNestedSubDirAndNoOtherFiles(): void
    {
        $fileName = 'dir1/dir2/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(3))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2'],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            );
        $this->filesystem->expects(self::exactly(2))
            ->method('listKeys')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2/'],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/']
            )
            ->willReturn(['keys' => [], 'dirs' => []]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2'],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1']
            );

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromNestedSubDirAndThereAreOtherFilesInFirstLevelDir(): void
    {
        $fileName = 'dir1/dir2/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(3))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2'],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            );
        $this->filesystem->expects(self::exactly(2))
            ->method('listKeys')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2/'],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/']
            )
            ->willReturnOnConsecutiveCalls(
                ['keys' => [], 'dirs' => []],
                ['keys' => [self::TEST_FILE_SYSTEM_NAME . '/dir1/another_file.txt'], 'dirs' => []]
            );
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromNestedSubDirAndThereAreOtherFilesInSecondLevelDir(): void
    {
        $fileName = 'dir1/dir2/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2/')
            ->willReturn(['keys' => [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2/another_file.txt'], 'dirs' => []]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromNestedSubDirAndThereAreOtherDirsInFirstLevelDir(): void
    {
        $fileName = 'dir1/dir2/file.txt';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(3))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileName],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2'],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                true
            );
        $this->filesystem->expects(self::exactly(2))
            ->method('listKeys')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2/'],
                [self::TEST_FILE_SYSTEM_NAME . '/dir1/']
            )
            ->willReturnOnConsecutiveCalls(
                ['keys' => [], 'dirs' => []],
                ['keys' => [], 'dirs' => [self::TEST_FILE_SYSTEM_NAME . '/dir1/another_dir']]
            );
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName);
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir1/dir2');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromSubDirAndNoOtherFilesAndFileNameHaveLeadingSlash(): void
    {
        $fileNameWithoutLeadingSlash = 'dir/file.txt';
        $fileName = '/' . $fileNameWithoutLeadingSlash;

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileNameWithoutLeadingSlash)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/' . $fileNameWithoutLeadingSlash],
                [self::TEST_FILE_SYSTEM_NAME . '/dir']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir/')
            ->willReturn(['keys' => [], 'dirs' => []]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileNameWithoutLeadingSlash);
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteFileFromSubDirAndNoOtherFilesAndFileNameHaveLeadingSlashForNotSubDirAwareManager(): void
    {
        $fileNameWithoutLeadingSlash = 'dir/file.txt';
        $fileName = '/' . $fileNameWithoutLeadingSlash;

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with($fileNameWithoutLeadingSlash)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('isDirectory')
            ->withConsecutive(
                [$fileNameWithoutLeadingSlash],
                ['dir']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('dir/')
            ->willReturn(['keys' => [], 'dirs' => []]);
        $this->filesystem->expects(self::once())
            ->method('delete')
            ->with($fileNameWithoutLeadingSlash);
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with('dir');

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteFile($fileName);
    }

    public function testDeleteAllFiles(): void
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/')
            ->willReturn([
                'keys' => [self::TEST_FILE_SYSTEM_NAME . '/file1', self::TEST_FILE_SYSTEM_NAME . '/file2'],
                'dirs' => [self::TEST_FILE_SYSTEM_NAME . '/dir1']
            ]);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/file1'],
                [self::TEST_FILE_SYSTEM_NAME . '/file2']
            );
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir1');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles();
    }

    public function testDeleteAllFilesForNotSubDirAwareManager(): void
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('')
            ->willReturn([
                'keys' => ['file1', 'file2'],
                'dirs' => ['dir1']
            ]);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['file1'],
                ['file2']
            );
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with('dir1');

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteAllFiles();
    }

    /**
     * E.g. this may happens when AwsS3 or GoogleCloudStorage adapters are used
     */
    public function testDeleteAllFilesWhenAdapterReturnsOnlyKeys(): void
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/')
            ->willReturn([self::TEST_FILE_SYSTEM_NAME . '/file1', self::TEST_FILE_SYSTEM_NAME . '/file2']);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/file1'],
                [self::TEST_FILE_SYSTEM_NAME . '/file2']
            );
        $this->filesystemAdapter->expects(self::never())
            ->method('delete');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles();
    }

    public function testDeleteAllFilesByPrefix(): void
    {
        $prefix = 'prefix';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/' . $prefix;

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $prefix)
            ->willReturn([
                'keys' => [$directory . '_file1', $directory . '_file2'],
                'dirs' => [$directory . '/dir1']
            ]);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$directory . '_file1'],
                [$directory . '_file2']
            );
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with($directory . '/dir1');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles($prefix);
    }

    public function testDeleteAllFilesByPrefixForNotSubDirAwareManager(): void
    {
        $prefix = 'prefix';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with($prefix)
            ->willReturn([
                'keys' => [$prefix . '_file1', $prefix . '_file2'],
                'dirs' => [$prefix . '/dir1']
            ]);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$prefix . '_file1'],
                [$prefix . '_file2']
            );
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with($prefix . '/dir1');

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteAllFiles($prefix);
    }

    public function testDeleteAllFilesByPrefixWhenPrefixIsSlash(): void
    {
        $prefix = '/';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/')
            ->willReturn([
                'keys' => [$directory . 'file1', $directory . 'file2'],
                'dirs' => [$directory . 'dir1']
            ]);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [self::TEST_FILE_SYSTEM_NAME . '/file1'],
                [self::TEST_FILE_SYSTEM_NAME . '/file2']
            );
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/dir1');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles($prefix);
    }

    public function testDeleteAllFilesByPrefixWhenPrefixIsSlashForNotSubDirAwareManager(): void
    {
        $prefix = '/';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('')
            ->willReturn([
                'keys' => ['file1', 'file2'],
                'dirs' => ['dir1']
            ]);
        $this->filesystem->expects(self::never())
            ->method('isDirectory');
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                ['file1'],
                ['file2']
            );
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with('dir1');

        $fileManager = $this->getFileManager(false);

        $fileManager->deleteAllFiles($prefix);
    }

    public function testDeleteAllFilesByPrefixWithTailingSlash(): void
    {
        $prefix = 'prefix/';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/prefix';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $prefix)
            ->willReturn([
                'keys' => [$directory . '_file1', $directory . '_file2'],
                'dirs' => [$directory . '/dir1']
            ]);
        $this->filesystem->expects(self::once())
            ->method('isDirectory')
            ->with($directory)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$directory . '_file1'],
                [$directory . '_file2']
            );
        $this->filesystemAdapter->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$directory . '/dir1'],
                [$directory]
            );

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles($prefix);
    }

    public function testDeleteAllFilesByPrefixWithTailingSlashWhenFilesystemNotSupportDirectories(): void
    {
        $prefix = 'prefix/';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/prefix';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $prefix)
            ->willReturn([
                'keys' => [$directory . '_file1', $directory . '_file2'],
                'dirs' => [$directory . '/dir1']
            ]);
        $this->filesystem->expects(self::once())
            ->method('isDirectory')
            ->with($directory)
            ->willReturn(false);
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$directory . '_file1'],
                [$directory . '_file2']
            );
        $this->filesystemAdapter->expects(self::once())
            ->method('delete')
            ->with($directory . '/dir1');

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles($prefix);
    }

    public function testDeleteAllFilesByPrefixWithTwoTailingSlashes(): void
    {
        $prefix = 'prefix//';
        $directory = self::TEST_FILE_SYSTEM_NAME . '/prefix';

        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/prefix/')
            ->willReturn([
                'keys' => [$directory . '_file1', $directory . '_file2'],
                'dirs' => [$directory . '/dir1']
            ]);
        $this->filesystem->expects(self::once())
            ->method('isDirectory')
            ->with($directory)
            ->willReturn(true);
        $this->filesystem->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$directory . '_file1'],
                [$directory . '_file2']
            );
        $this->filesystemAdapter->expects(self::exactly(2))
            ->method('delete')
            ->withConsecutive(
                [$directory . '/dir1'],
                [$directory]
            );

        $fileManager = $this->getFileManager(true);

        $fileManager->deleteAllFiles($prefix);
    }

    public function testWriteToStorage(): void
    {
        $content = 'Test data';
        $fileName = 'file.txt';

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

    public function testWriteToStorageForNotSubDirAwareManager(): void
    {
        $content = 'Test data';
        $fileName = 'file.txt';

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

    public function testWriteToStorageWhenFlushFailed(): void
    {
        $this->expectException(FlushFailedException::class);
        $this->expectExceptionMessage(
            sprintf('Failed to flush data to the "%s/file.txt" file.', self::TEST_FILE_SYSTEM_NAME)
        );

        $content = 'Test data';
        $fileName = 'file.txt';

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

    public function testWriteToStorageWhenFlushFailedForNotSubDirAwareManager(): void
    {
        $this->expectException(FlushFailedException::class);
        $this->expectExceptionMessage('Failed to flush data to the "file.txt" file.');

        $content = 'Test data';
        $fileName = 'file.txt';

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
            ->with($fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with($fileName);

        $fileManager = $this->getFileManager(false);

        $fileManager->writeToStorage($content, $fileName);
    }

    public function testWriteToStorageWhenFlushFailedWithCustomSubDirectory(): void
    {
        $this->expectException(FlushFailedException::class);
        $this->expectExceptionMessage('Failed to flush data to the "testSubDir/file.txt" file.');

        $content = 'Test data';
        $fileName = 'file.txt';

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
            ->with('testSubDir/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with('testSubDir/' . $fileName);

        $fileManager = $this->getFileManager(true, 'testSubDir');

        $fileManager->writeToStorage($content, $fileName);
    }

    public function testWriteToStorageWhenFileNameIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The file name must not be empty.');

        $fileManager = $this->getFileManager(true);

        $fileManager->writeToStorage('Test data', '');
    }

    public function testWriteFileToStorage(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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

    public function testWriteFileToStorageForNotSubDirAwareManager(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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

    public function testWriteFileToStorageWhenLocalFilePathIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The local path must not be empty.');

        $fileManager = $this->getFileManager(true);

        $fileManager->writeFileToStorage('', 'file.txt');
    }

    public function testWriteFileToStorageWhenFileNameIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The file name must not be empty.');

        $fileManager = $this->getFileManager(true);

        $fileManager->writeFileToStorage(__DIR__ . '/Fixtures/test.txt', '');
    }

    public function testWriteStreamToStorage(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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

    public function testWriteStreamToStorageForNotSubDirAwareManager(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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

    public function testWriteStreamToStorageWhenFlushFailed(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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
            self::assertEquals(
                sprintf('Failed to flush data to the "%s/file.txt" file.', self::TEST_FILE_SYSTEM_NAME),
                $e->getMessage()
            );
            // test that the input stream is closed
            self::assertFalse($srcStream->cast(1));
        }
    }

    public function testWriteStreamToStorageWhenFlushFailedForNotSubDirAwareManager(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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
            ->with($fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with($fileName);

        $fileManager = $this->getFileManager(false);

        try {
            $fileManager->writeStreamToStorage($srcStream, $fileName);
            self::fail('Expected FlushFailedException');
        } catch (FlushFailedException $e) {
            self::assertEquals(
                'Failed to flush data to the "file.txt" file.',
                $e->getMessage()
            );
            // test that the input stream is closed
            self::assertFalse($srcStream->cast(1));
        }
    }

    public function testWriteStreamToStorageWhenFlushFailedWithCustomSubDirectory(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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
            ->with('testSubDir/' . $fileName)
            ->willReturn($resultStream);
        $this->filesystem->expects(self::once())
            ->method('removeFromRegister')
            ->with('testSubDir/' . $fileName);

        $fileManager = $this->getFileManager(true, 'testSubDir');

        try {
            $fileManager->writeStreamToStorage($srcStream, $fileName);
            self::fail('Expected FlushFailedException');
        } catch (FlushFailedException $e) {
            self::assertEquals(
                'Failed to flush data to the "testSubDir/file.txt" file.',
                $e->getMessage()
            );
            // test that the input stream is closed
            self::assertFalse($srcStream->cast(1));
        }
    }

    public function testWriteStreamToStorageWithEmptyStreamAndAvoidWriteEmptyStream(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/emptyFile.txt';
        $fileName = 'file.txt';

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

    public function testWriteStreamToStorageWithEmptyStream(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/emptyFile.txt';
        $fileName = 'file.txt';

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

    public function testWriteStreamToStorageAndAvoidWriteEmptyStream(): void
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'file.txt';

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

    public function testWriteStreamToStorageWhenFileNameIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The file name must not be empty.');

        $this->filesystem->expects(self::never())
            ->method('createStream');
        $this->filesystem->expects(self::never())
            ->method('removeFromRegister');

        $fileManager = $this->getFileManager(true);

        $fileManager->writeStreamToStorage(new LocalStream(__DIR__ . '/Fixtures/test.txt'), '');
    }

    public function testWriteToTemporaryFile(): void
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

    public function testWriteStreamToTemporaryFile(): void
    {
        $content = 'Test data';

        $srcStream = new InMemoryBuffer($this->filesystem, 'file.txt');
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
            if (!str_contains($e->getMessage(), 'cannot be opened')) {
                throw $e;
            }
            /**
             * cannot open temporary file - ignore this error
             * @see \Gaufrette\Stream\Local::open
             */
        }
    }

    public function testGetTemporaryFileNameWithoutSuggestedFileName(): void
    {
        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName();
        self::assertNotEmpty($tmpFileName);

        $parts = explode(DIRECTORY_SEPARATOR, $tmpFileName);
        if (str_starts_with($tmpFileName, DIRECTORY_SEPARATOR)) {
            array_shift($parts);
        }
        foreach ($parts as $part) {
            self::assertNotEmpty(
                $part,
                sprintf('Several directory separators follow each other. File Name: %s', $tmpFileName)
            );
        }
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithoutExtension(): void
    {
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true)));

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);

        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $suggestedFileName, $tmpFileName);
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithExtension(): void
    {
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true))) . '.txt';

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);

        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $suggestedFileName, $tmpFileName);
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithoutExtensionWhenFileAlreadyExists(): void
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

    public function testGetTemporaryFileNameWithSuggestedFileNameWithExtensionWhenFileAlreadyExists(): void
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

    public function testGetTemporaryFileNameWithPathInSuggestedFileName(): void
    {
        $suggestedFileName = 'some/path/TestFile.txt';
        $expectedFileName = 'TestFile.txt';

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);

        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $expectedFileName, $tmpFileName);
        self::assertStringNotContainsString('some/path/', $tmpFileName);

        self::assertEquals(9, file_put_contents($tmpFileName, 'test_data'));
        self::assertEquals('test_data', file_get_contents($tmpFileName));
        self::assertTrue(unlink($tmpFileName));
    }

    public function testGetTemporaryFileNameWithExtraCharsInSuggestedFileName(): void
    {
        $suggestedFileName = 'T\e:s|t<F>i*l?e>:*:<.txt';
        $expectedFileName = 'T_e_s_t_F_i_l_e_.txt';

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);

        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $expectedFileName, $tmpFileName);

        self::assertEquals(9, file_put_contents($tmpFileName, 'test_data'));
        self::assertEquals('test_data', file_get_contents($tmpFileName));
        self::assertTrue(unlink($tmpFileName));
    }

    public function testGetTemporaryFileFromExtraLongSuggestedFileName(): void
    {
        $suggestedFileName = 'Fuscebibendumleointemporhendreritmaurisestsemperodiovestibulumconguearcuera'
            . 'tegeterateraesentacorcjustojrcivariusnatoquepenatibusetmagnisdisparturientmontesFuscebibend'
            . 'umleointemporhendreritmaurisestsemperodiovestibulumconguearcuerategeterateraesentacorcjusto'
            . 'jrcivariusnatoquepenatibusetmagnisdisparturientmontes.png';
        $expectedFileName = 'toquepenatibusetmagnisdisparturientmontesFuscebibendumleointemporhendreritmaur'
            . 'isestsemperodiovestibulumconguearcuerategeterateraesentacorcjustojrcivariusnatoquepenatibuset'
            . 'magnisdisparturientmontes.png';

        $fileManager = $this->getFileManager(true);

        $tmpFileName = $fileManager->getTemporaryFileName($suggestedFileName);

        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $expectedFileName, $tmpFileName);
        self::assertStringNotContainsString('some/path/', $tmpFileName);

        self::assertEquals(9, file_put_contents($tmpFileName, 'test_data'));
        self::assertEquals('test_data', file_get_contents($tmpFileName));
        self::assertTrue(unlink($tmpFileName));
    }
}
