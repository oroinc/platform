<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\Stream\Local as LocalStream;
use Gaufrette\StreamMode;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\GaufretteBundle\FileManager;
use Symfony\Component\Filesystem\Exception\IOException;

class FileManagerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_FILE_SYSTEM_NAME = 'testFileSystem';
    const TEST_PROTOCOL         = 'testProtocol';

    /** @var \PHPUnit\Framework\MockObject\MockObject|Filesystem */
    protected $filesystem;

    /** @var FileManager */
    protected $fileManager;

    public function setUp()
    {
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filesystemMap = $this->getMockBuilder(FilesystemMap::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filesystemMap->expects($this->once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn($this->filesystem);

        $this->fileManager = new FileManager(self::TEST_FILE_SYSTEM_NAME);
        $this->fileManager->setFilesystemMap($filesystemMap);
        $this->fileManager->setProtocol(self::TEST_PROTOCOL);
    }

    public function testGetProtocol()
    {
        self::assertEquals(self::TEST_PROTOCOL, $this->fileManager->getProtocol());
    }

    public function testGetFilePath()
    {
        self::assertEquals(
            sprintf('%s://%s/test.txt', self::TEST_PROTOCOL, self::TEST_FILE_SYSTEM_NAME),
            $this->fileManager->getFilePath('test.txt')
        );
    }

    /**
     * @expectedException \Oro\Bundle\GaufretteBundle\Exception\ProtocolConfigurationException
     */
    public function testGetFilePathWhenProtocolIsNotConfigured()
    {
        $this->fileManager->setProtocol('');
        $this->fileManager->getFilePath('test.txt');
    }

    public function testFindAllFiles()
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with(self::identicalTo(''))
            ->willReturn([
                'keys' => ['file1', 'file2'],
                'dirs' => ['dir1']
            ]);

        self::assertEquals(
            ['file1', 'file2'],
            $this->fileManager->findFiles()
        );
    }

    public function testFindFilesByPrefix()
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('prefix')
            ->willReturn([
                'keys' => ['file1', 'file2'],
                'dirs' => ['dir1']
            ]);

        self::assertEquals(
            ['file1', 'file2'],
            $this->fileManager->findFiles('prefix')
        );
    }

    public function testFindFilesWhenNoFilesFound()
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('prefix')
            ->willReturn([]);

        self::assertSame(
            [],
            $this->fileManager->findFiles('prefix')
        );
    }

    /**
     * E.g. this may happens when AwsS3 or GoogleCloudStorage adapters are used
     */
    public function testFindFilesWhenAdapterReturnsOnlyKeys()
    {
        $this->filesystem->expects(self::once())
            ->method('listKeys')
            ->with('prefix')
            ->willReturn(['file1', 'file2']);

        self::assertEquals(
            ['file1', 'file2'],
            $this->fileManager->findFiles('prefix')
        );
    }

    public function testGetFileByFileName()
    {
        $fileName = 'testFile.txt';

        $file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem->expects($this->never())
            ->method('has');
        $this->filesystem->expects($this->once())
            ->method('get')
            ->with($fileName)
            ->willReturn($file);

        $this->assertSame($file, $this->fileManager->getFile($fileName));
    }

    public function testGetFileWhenFileDoesNotExistAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(false);
        $this->filesystem->expects($this->never())
            ->method('get');

        $this->assertNull($this->fileManager->getFile($fileName, false));
    }

    public function testGetFileWhenFileExistsAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('get')
            ->with($fileName)
            ->willReturn($file);

        $this->assertSame($file, $this->fileManager->getFile($fileName, false));
    }

    /**
     * @expectedException \Gaufrette\Exception\FileNotFound
     * @expectedExceptionMessage  The file "testFile.txt" was not found.
     */
    public function testGetStreamWhenFileDoesNotExist()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(false);
        $this->filesystem->expects($this->never())
            ->method('createStream');

        $this->fileManager->getStream($fileName);
    }

    public function testGetStreamWhenFileDoesNotExistAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(false);
        $this->filesystem->expects($this->never())
            ->method('createStream');

        $this->assertNull($this->fileManager->getStream($fileName, false));
    }

    public function testGetStreamWhenFileExistsAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $file = tmpfile();
        fwrite($file, 'file content ...');
        fseek($file, 0);

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($file);

        $stream = $this->fileManager->getStream($fileName, false);

        $this->assertInternalType('resource', $stream);
        $this->assertSame($file, $stream);

        fclose($file);
    }

    public function testGetFileContentByFileName()
    {
        $fileName = 'testFile.txt';
        $fileContent = 'test data';

        $file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file->expects($this->once())
            ->method('getContent')
            ->willReturn($fileContent);

        $this->filesystem->expects($this->never())
            ->method('has');
        $this->filesystem->expects($this->once())
            ->method('get')
            ->with($fileName)
            ->willReturn($file);

        $this->assertEquals($fileContent, $this->fileManager->getFileContent($fileName));
    }

    public function testGetFileContentWhenFileDoesNotExistAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(false);
        $this->filesystem->expects($this->never())
            ->method('get');

        $this->assertNull($this->fileManager->getFileContent($fileName, false));
    }

    public function testGetFileContentWhenFileExistsAndRequestedIgnoreException()
    {
        $fileName = 'testFile.txt';
        $fileContent = 'test data';

        $file = $this->getMockBuilder(File::class)
            ->disableOriginalConstructor()
            ->getMock();
        $file->expects($this->once())
            ->method('getContent')
            ->willReturn($fileContent);

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('get')
            ->with($fileName)
            ->willReturn($file);

        $this->assertEquals($fileContent, $this->fileManager->getFileContent($fileName, false));
    }

    public function testDeleteFile()
    {
        $fileName = 'text.txt';

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('delete')
            ->with($fileName);

        $this->fileManager->deleteFile($fileName);
    }

    public function testDeleteFileForNotExistingFile()
    {
        $fileName = 'text.txt';

        $this->filesystem->expects($this->once())
            ->method('has')
            ->with($fileName)
            ->willReturn(false);
        $this->filesystem->expects($this->never())
            ->method('delete');

        $this->fileManager->deleteFile($fileName);
    }

    public function testDeleteFileWhenFileNameIsEmpty()
    {
        $this->filesystem->expects($this->never())
            ->method('has');
        $this->filesystem->expects($this->never())
            ->method('delete');

        $this->fileManager->deleteFile(null);
    }

    public function testWriteToStorage()
    {
        $content = 'Test data';
        $fileName = 'test2.txt';

        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);

        $this->fileManager->writeToStorage($content, $fileName);

        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);
        $this->assertEquals($content, $resultStream->read(100));
    }

    public function testWriteFileToStorage()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);

        $this->fileManager->writeFileToStorage($localFilePath, $fileName);

        $resultStream->open(new StreamMode('rb+'));
        $resultStream->seek(0);
        $this->assertStringEqualsFile($localFilePath, $resultStream->read(100));
    }

    public function testWriteStreamToStorage()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);

        $result = $this->fileManager->writeStreamToStorage($srcStream, $fileName);

        $resultStream->open(new StreamMode('rb'));
        $resultStream->seek(0);
        $this->assertStringEqualsFile($localFilePath, $resultStream->read(100));
        $this->assertTrue($result);
        // double check if input stream is closed
        $this->assertFalse($srcStream->cast(1));
    }

    public function testWriteStreamToStorageWithEmptyStreamAndAvoidWriteEmptyStream()
    {
        $localFilePath = __DIR__ . '/Fixtures/emptyFile.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);

        $this->filesystem->expects($this->never())
            ->method('createStream')
            ->with($fileName);

        $result = $this->fileManager->writeStreamToStorage($srcStream, $fileName, true);

        $this->assertFalse($result);
        // double check if input stream is closed
        $this->assertFalse($srcStream->cast(1));
    }

    public function testWriteStreamToStorageWithEmptyStream()
    {
        $localFilePath = __DIR__ . '/Fixtures/emptyFile.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);

        $result = $this->fileManager->writeStreamToStorage($srcStream, $fileName);

        $resultStream->open(new StreamMode('rb'));
        $resultStream->seek(0);
        $this->assertEmpty($resultStream->read(100));
        $this->assertTrue($result);
        // double check if input stream is closed
        $this->assertFalse($srcStream->cast(1));
    }

    public function testWriteStreamToStorageAndAvoidWriteEmptyStream()
    {
        $localFilePath = __DIR__ . '/Fixtures/test.txt';
        $fileName = 'test2.txt';

        $srcStream = new LocalStream($localFilePath);
        $resultStream = new InMemoryBuffer($this->filesystem, $fileName);

        $this->filesystem->expects($this->once())
            ->method('createStream')
            ->with($fileName)
            ->willReturn($resultStream);

        $result = $this->fileManager->writeStreamToStorage($srcStream, $fileName, true);

        $resultStream->open(new StreamMode('rb'));
        $resultStream->seek(0);
        $this->assertStringEqualsFile($localFilePath, $resultStream->read(100));
        $this->assertTrue($result);
        // double check if input stream is closed
        $this->assertFalse($srcStream->cast(1));
    }

    public function testWriteToTemporaryFile()
    {
        $content = 'Test data';

        $resultFile = null;
        try {
            $resultFile = $this->fileManager->writeToTemporaryFile($content);
            try {
                self::assertEquals($content, file_get_contents($resultFile->getRealPath()));
            } finally {
                @unlink($resultFile->getRealPath());
            }
        } catch (IOException $e) {
            // no access to temporary file - ignore this error
        }
    }

    public function testStreamWriteToTemporaryFile()
    {
        $content = 'Test data';

        $srcStream = new InMemoryBuffer($this->filesystem, 'test.txt');
        $srcStream->open(new StreamMode('wb+'));
        $srcStream->write($content);
        $srcStream->seek(0);
        $srcStream->close();

        $resultFile = null;
        try {
            $resultFile = $this->fileManager->writeStreamToTemporaryFile($srcStream);
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
        $tmpFileName = $this->fileManager->getTemporaryFileName();
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
        $tmpFileName = $this->fileManager->getTemporaryFileName($suggestedFileName);
        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $suggestedFileName, $tmpFileName);
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithExtension()
    {
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true))) . '.txt';
        $tmpFileName = $this->fileManager->getTemporaryFileName($suggestedFileName);
        self::assertNotEmpty($tmpFileName);
        self::assertStringEndsWith(DIRECTORY_SEPARATOR . $suggestedFileName, $tmpFileName);
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithoutExtensionWhenFileAlreadyExists()
    {
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true)));
        $tmpFileName = $this->fileManager->getTemporaryFileName($suggestedFileName);
        try {
            if (false !== @file_put_contents($tmpFileName, 'test')) {
                // guard
                self::assertFileExists($tmpFileName, 'guard');

                $anotherTmpFileName = $this->fileManager->getTemporaryFileName($suggestedFileName);
                self::assertNotEmpty($anotherTmpFileName);
                self::assertNotEquals($tmpFileName, $anotherTmpFileName);
                self::assertFileNotExists($anotherTmpFileName);
            }
        } finally {
            @unlink($tmpFileName);
        }
    }

    public function testGetTemporaryFileNameWithSuggestedFileNameWithExtensionWhenFileAlreadyExists()
    {
        $fileExtension = '.txt';
        $suggestedFileName = sprintf('TestFile%s', str_replace('.', '', uniqid('', true))) . $fileExtension;
        $tmpFileName = $this->fileManager->getTemporaryFileName($suggestedFileName);
        try {
            if (false !== @file_put_contents($tmpFileName, 'test')) {
                // guard
                self::assertFileExists($tmpFileName, 'guard');

                $anotherTmpFileName = $this->fileManager->getTemporaryFileName($suggestedFileName);
                self::assertNotEmpty($anotherTmpFileName);
                self::assertNotEquals($tmpFileName, $anotherTmpFileName);
                self::assertStringEndsWith($fileExtension, $anotherTmpFileName);
                self::assertFileNotExists($anotherTmpFileName);
            }
        } finally {
            @unlink($tmpFileName);
        }
    }
}
