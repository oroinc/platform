<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Gaufrette\Exception\FileNotFound as GaufretteFileNotFoundException;
use Gaufrette\Filesystem;
use Gaufrette\Stream\InMemoryBuffer;
use Gaufrette\StreamMode;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Exception\ProtocolNotSupportedException;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Mapper\ClientMimeTypeMapper;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestFile;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestGaufretteAdapterWithMetadata;
use Oro\Bundle\AttachmentBundle\Tools\ExternalFileFactory;
use Oro\Bundle\AttachmentBundle\Validator\ProtocolValidatorInterface;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FileManagerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_FILE_SYSTEM_NAME = 'testAttachments';
    private const TEST_PROTOCOL = 'testProtocol';

    /** @var Filesystem|\PHPUnit\Framework\MockObject\MockObject */
    private $filesystem;

    /** @var ProtocolValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $protocolValidator;

    /** @var ExternalFileFactory */
    private $externalFileFactory;

    /** @var FileManager */
    private $fileManager;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->protocolValidator = $this->createMock(ProtocolValidatorInterface::class);
        $this->externalFileFactory = $this->createMock(ExternalFileFactory::class);

        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME)
            ->willReturn($this->filesystem);

        $this->fileManager = new FileManager(
            self::TEST_FILE_SYSTEM_NAME,
            $this->protocolValidator,
            new ClientMimeTypeMapper(),
            $this->externalFileFactory
        );
        $this->fileManager->setProtocol(self::TEST_PROTOCOL);
        $this->fileManager->useSubDirectory(true);
        $this->fileManager->setFilesystemMap($filesystemMap);
    }

    private function createFileEntity(
        string $originalFileName = 'testFile.txt',
        string $fileName = 'testFile.txt'
    ): TestFile {
        $fileEntity = new TestFile();
        if (null !== $originalFileName) {
            $fileEntity->setOriginalFilename($originalFileName);
        }
        if (null !== $fileName) {
            $fileEntity->setFilename($fileName);
        }

        return $fileEntity;
    }

    public function testGetContentByFileEntity(): void
    {
        $fileEntity = $this->createFileEntity();
        $fileContent = 'test data';

        $file = $this->createMock(\Gaufrette\File::class);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename());
        $file->expects(self::once())
            ->method('setName')
            ->with($fileEntity->getFilename());

        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn($file);

        self::assertEquals($fileContent, $this->fileManager->getContent($fileEntity));
    }

    public function testGetContentWhenFileDoesNotExist(): void
    {
        $this->expectException(GaufretteFileNotFoundException::class);

        $fileName = 'testFile.txt';

        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileName)
            ->willThrowException(new GaufretteFileNotFoundException($fileName));

        $this->fileManager->getContent($fileName);
    }

    public function testGetContentWhenFileIsExternallyStored(): void
    {
        $file = new File();
        $file->setExternalUrl('http://example.org/image.png');

        $this->filesystem->expects(self::never())
            ->method(self::anything());

        self::assertNull($this->fileManager->getContent($file));
    }

    public function testCreateFileEntity(): void
    {
        $path = __DIR__ . '/../Fixtures/testFile/test.txt';

        $this->protocolValidator->expects(self::never())
            ->method('isSupportedProtocol');

        $result = $this->fileManager->createFileEntity($path);
        self::assertEquals('test.txt', $result->getOriginalFilename());
        self::assertFileEquals($path, $result->getFile()->getPathname());
    }

    public function testSetFileFromPath(): void
    {
        $path = __DIR__ . '/../Fixtures/testFile/test.txt';

        $this->protocolValidator->expects(self::never())
            ->method('isSupportedProtocol');

        $file = $this->createFileEntity();

        $this->fileManager->setFileFromPath($file, $path);
        self::assertEquals('test.txt', $file->getOriginalFilename());
        self::assertFileEquals($path, $file->getFile()->getPathname());
    }

    public function testSetExternalFileFromExternalUrl(): void
    {
        $file = $this->createFileEntity();
        $url = 'http://example.org/image.png';

        $externalFile = new ExternalFile($url, 'original-image.png', 4242, 'image/png');
        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromUrl')
            ->with($url)
            ->willReturn($externalFile);

        $this->fileManager->setExternalFileFromUrl($file, $url);
        self::assertEquals('original-image.png', $file->getOriginalFilename());
        self::assertSame($externalFile, $file->getFile());
    }

    /**
     * @dataProvider fileWithoutProtocolDataProvider
     */
    public function testCreateFileEntityWhenProtocolIsNotSpecified(string $path): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->protocolValidator->expects(self::never())
            ->method('isSupportedProtocol');

        $this->fileManager->createFileEntity($path);
    }

    public function fileWithoutProtocolDataProvider(): array
    {
        return [
            [''],
            [' '],
            ['/file.txt'],
            ['\\server\file.txt'],
            ['C:\file.txt'],
            ['c:/file.txt']
        ];
    }

    /**
     * @dataProvider fileWithoutProtocolDataProvider
     */
    public function testSetFileFromPathWhenProtocolIsNotSpecified(string $path): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->protocolValidator->expects(self::never())
            ->method('isSupportedProtocol');

        $this->fileManager->setFileFromPath($this->createFileEntity(), $path);
    }

    /**
     * @dataProvider supportedFileProtocolDataProvider
     */
    public function testCreateFileEntityWhenProtocolIsSupported(string $path, string $expectedProtocol): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->protocolValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($expectedProtocol)
            ->willReturn(true);

        $this->fileManager->createFileEntity($path);
    }

    public function supportedFileProtocolDataProvider(): array
    {
        return [
            ['file://file.txt', 'file'],
            ['File://file.txt', 'file'],
            [' FILE://file.txt ', 'file']
        ];
    }

    /**
     * @dataProvider supportedFileProtocolDataProvider
     */
    public function testSetFileFromPathWhenProtocolIsSupported(string $path, string $expectedProtocol): void
    {
        $this->expectException(FileNotFoundException::class);

        $this->protocolValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($expectedProtocol)
            ->willReturn(true);

        $this->fileManager->setFileFromPath($this->createFileEntity(), $path);
    }

    /**
     * @dataProvider notSupportedFileProtocolDataProvider
     */
    public function testCreateFileEntityWhenProtocolIsNotSupported(string $path, string $expectedProtocol): void
    {
        $this->expectException(ProtocolNotSupportedException::class);

        $this->protocolValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($expectedProtocol)
            ->willReturn(false);

        $this->fileManager->createFileEntity($path);
    }

    public function notSupportedFileProtocolDataProvider(): array
    {
        return [
            ['phar://test.phar/file.txt', 'phar'],
            ['Phar://test.phar/file.txt', 'phar'],
            [' PHAR://test.phar/file.txt ', 'phar']
        ];
    }

    /**
     * @dataProvider notSupportedFileProtocolDataProvider
     */
    public function testSetFileFromPathWhenProtocolIsNotSupported(string $path, string $expectedProtocol): void
    {
        $this->expectException(ProtocolNotSupportedException::class);

        $this->protocolValidator->expects(self::once())
            ->method('isSupportedProtocol')
            ->with($expectedProtocol)
            ->willReturn(false);

        $this->fileManager->setFileFromPath($this->createFileEntity(), $path);
    }

    public function testCreateFileEntityForNotExistingFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $path = __DIR__ . '/../Fixtures/testFile/not_existed.txt';

        $this->fileManager->createFileEntity($path);
    }

    public function testCloneFileEntity(): void
    {
        $fileEntity = $this->createFileEntity();

        $file = $this->createMock(\Gaufrette\File::class);
        $fileContent = 'test';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn($file);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename());
        $file->expects(self::once())
            ->method('setName')
            ->with($fileEntity->getFilename());

        $clonedFileEntity = $this->fileManager->cloneFileEntity($fileEntity);

        self::assertNotSame($fileEntity, $clonedFileEntity);
        self::assertEquals($fileEntity->getOriginalFilename(), $clonedFileEntity->getOriginalFilename());
        self::assertNull($clonedFileEntity->getFilename());
        self::assertNotNull($clonedFileEntity->getFile());
        self::assertEquals(
            $fileContent,
            file_get_contents($clonedFileEntity->getFile()->getRealPath())
        );
    }

    public function testCloneFileEntityWhenFileDoesNotExist(): void
    {
        $fileEntity = $this->createFileEntity();

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('get');

        $clonedFileEntity = $this->fileManager->cloneFileEntity($fileEntity);

        self::assertNull($clonedFileEntity);
    }

    public function testGetFileFromFileEntity(): void
    {
        $fileEntity = $this->createFileEntity();

        $file = $this->createMock(\Gaufrette\File::class);
        $fileContent = 'test';

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn(true);
        $this->filesystem->expects(self::once())
            ->method('get')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn($file);
        $file->expects(self::once())
            ->method('getContent')
            ->willReturn($fileContent);
        $file->expects(self::once())
            ->method('getName')
            ->willReturn(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename());
        $file->expects(self::once())
            ->method('setName')
            ->with($fileEntity->getFilename());

        $innerFile = $this->fileManager->getFileFromFileEntity($fileEntity, false);

        self::assertNotNull($innerFile);
        self::assertEquals(
            $fileContent,
            file_get_contents($innerFile->getRealPath())
        );
    }

    public function testGetFileFromFileEntityWhenExternalUrls(): void
    {
        $fileEntity = $this->createFileEntity();
        $fileEntity->setExternalUrl('http://example.org/image.png');

        $this->filesystem->expects(self::never())
            ->method(self::anything());

        $externalFile = new ExternalFile($fileEntity->getExternalUrl());
        $this->externalFileFactory
            ->expects(self::once())
            ->method('createFromFile')
            ->with($fileEntity)
            ->willReturn($externalFile);

        self::assertSame($externalFile, $this->fileManager->getFileFromFileEntity($fileEntity, false));
    }

    public function testGetFileFromFileEntityWhenFileDoesNotExist(): void
    {
        $fileEntity = $this->createFileEntity();

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn(false);
        $this->filesystem->expects(self::never())
            ->method('get');

        self::assertNull($this->fileManager->getFileFromFileEntity($fileEntity, false));
    }

    public function testGetFileFromFileEntityWhenFileDoesNotExistAndException(): void
    {
        $fileEntity = $this->createFileEntity();

        $this->filesystem->expects(self::never())
            ->method('has');
        $this->filesystem->expects(self::once())
            ->method('get')
            ->willThrowException(new FileNotFoundException());

        $this->expectException(FileNotFoundException::class);

        self::assertNull($this->fileManager->getFileFromFileEntity($fileEntity, true));
    }

    public function testPreUploadDeleteFile(): void
    {
        $fileEntity = $this->createFileEntity();
        $fileEntity
            ->setUuid(UUIDGenerator::v4())
            ->setFilename('test.txt')
            ->setOriginalFilename('test-orig.txt')
            ->setEmptyFile(true)
            ->setExtension('txt')
            ->setFileSize(100)
            ->setMimeType('text/plain');

        $this->fileManager->preUpload($fileEntity);

        self::assertNull($fileEntity->getOriginalFilename());
        self::assertNull($fileEntity->getExtension());
        self::assertNull($fileEntity->getMimeType());
        self::assertNull($fileEntity->getFileSize());
        self::assertEquals($fileEntity->getUuid(), $fileEntity->getFilename());
    }

    public function testPreUploadForUploadedFile(): void
    {
        $fileEntity = $this->createFileEntity();
        $file = new UploadedFile(__DIR__ . '/../Fixtures/testFile/test.txt', 'originalFile.csv', 'text/csv');
        $fileEntity
            ->setEmptyFile(false)
            ->setFile($file);

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::stringStartsWith(self::TEST_FILE_SYSTEM_NAME . '/'))
            ->willReturn(false);

        $this->fileManager->preUpload($fileEntity);

        self::assertEquals('originalFile.csv', $fileEntity->getOriginalFilename());
        self::assertEquals('csv', $fileEntity->getExtension());
        self::assertEquals('text/csv', $fileEntity->getMimeType());
        self::assertEquals(9, $fileEntity->getFileSize());
        self::assertNotEquals('testFile.txt', $fileEntity->getFilename());
    }

    public function testPreUploadForRegularFile(): void
    {
        $fileEntity = $this->createFileEntity();
        $file = new SymfonyFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $fileEntity
            ->setEmptyFile(false)
            ->setFile($file);

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::stringStartsWith(self::TEST_FILE_SYSTEM_NAME . '/'))
            ->willReturn(false);

        $this->fileManager->preUpload($fileEntity);

        self::assertEquals('testFile.txt', $fileEntity->getOriginalFilename());
        self::assertEquals('txt', $fileEntity->getExtension());
        self::assertEquals('text/plain', $fileEntity->getMimeType());
        self::assertEquals(9, $fileEntity->getFileSize());
        self::assertNotEquals('testFile.txt', $fileEntity->getFilename());
    }

    public function testPreUploadForWinZipFile()
    {
        $fileEntity = $this->createFileEntity();
        $file = new UploadedFile(
            __DIR__ . '/../Fixtures/testFile/test.zip',
            'original.zip',
            'application/x-zip-compressed'
        );
        $fileEntity
            ->setEmptyFile(false)
            ->setFile($file);

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::stringStartsWith(self::TEST_FILE_SYSTEM_NAME . '/'))
            ->willReturn(false);

        $this->fileManager->preUpload($fileEntity);

        self::assertEquals('original.zip', $fileEntity->getOriginalFilename());
        self::assertEquals('zip', $fileEntity->getExtension());
        self::assertEquals('application/zip', $fileEntity->getMimeType());
        self::assertEquals(123, $fileEntity->getFileSize());
        self::assertNotEquals('test.zip', $fileEntity->getFilename());
    }

    public function testPreUploadForExternalFile(): void
    {
        $fileEntity = $this->createFileEntity();
        $externalFile = new ExternalFile('http://example.org/image.png', 'original-image.png', 4242, 'image/png');
        $fileEntity->setFile($externalFile);

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::stringStartsWith(self::TEST_FILE_SYSTEM_NAME . '/'))
            ->willReturn(false);

        $this->fileManager->preUpload($fileEntity);

        self::assertEquals($externalFile->getOriginalName(), $fileEntity->getOriginalFilename());
        self::assertEquals($externalFile->getOriginalExtension(), $fileEntity->getExtension());
        self::assertEquals($externalFile->getMimeType(), $fileEntity->getMimeType());
        self::assertEquals($externalFile->getSize(), $fileEntity->getFileSize());
        self::assertEquals($externalFile->getUrl(), $fileEntity->getExternalUrl());
        self::assertNotEquals($externalFile->getFilename(), $fileEntity->getFilename());
    }

    public function testPreUploadForExternalFileWhenNoOriginalFilename(): void
    {
        $fileEntity = $this->createFileEntity();
        $externalFile = new ExternalFile('http://example.org/image.png', '', 4242, 'image/png');
        $fileEntity->setFile($externalFile);

        $this->filesystem->expects(self::once())
            ->method('has')
            ->with(self::stringStartsWith(self::TEST_FILE_SYSTEM_NAME . '/'))
            ->willReturn(false);

        $this->fileManager->preUpload($fileEntity);

        self::assertEquals($externalFile->getFilename(), $fileEntity->getOriginalFilename());
        self::assertEquals($externalFile->getExtension(), $fileEntity->getExtension());
        self::assertEquals($externalFile->getMimeType(), $fileEntity->getMimeType());
        self::assertEquals($externalFile->getSize(), $fileEntity->getFileSize());
        self::assertEquals($externalFile->getUrl(), $fileEntity->getExternalUrl());
        self::assertNotEquals($externalFile->getFilename(), $fileEntity->getFilename());
    }

    public function testPreUploadForExternalFileDoesNothingWhenNotValid(): void
    {
        $fileEntity = $this->createFileEntity();
        $externalFile = new ExternalFile('');
        $fileEntity->setFile($externalFile);

        $this->filesystem->expects(self::never())
            ->method('has');

        $this->fileManager->preUpload($fileEntity);

        self::assertEquals('testFile.txt', $fileEntity->getOriginalFilename());
        self::assertNull($fileEntity->getExternalUrl());
    }

    public function testUpload(): void
    {
        $fileEntity = $this->createFileEntity();
        $fileEntity->setEmptyFile(false);

        $file = new SymfonyFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $fileEntity->setFile($file);

        $memoryBuffer = new InMemoryBuffer($this->filesystem, 'test.txt');

        $this->filesystem->expects(self::once())
            ->method('createStream')
            ->with(self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename())
            ->willReturn($memoryBuffer);

        $adapter = $this->createMock(TestGaufretteAdapterWithMetadata::class);
        $this->filesystem->expects(self::any())
            ->method('getAdapter')
            ->willReturn($adapter);
        $adapter->expects(self::once())
            ->method('setMetadata')
            ->with(
                self::TEST_FILE_SYSTEM_NAME . '/' . $fileEntity->getFilename(),
                ['contentType' => $fileEntity->getMimeType()]
            );

        $this->fileManager->upload($fileEntity);

        $memoryBuffer->open(new StreamMode('rb+'));
        $memoryBuffer->seek(0);

        self::assertEquals('Test data', $memoryBuffer->read(100));
    }
}
