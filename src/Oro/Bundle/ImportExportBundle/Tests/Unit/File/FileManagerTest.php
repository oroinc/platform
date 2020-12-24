<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Gaufrette\File;
use Gaufrette\FilesystemInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileManagerTest extends TestCase
{
    use TempDirExtension;

    private const FILENAME = 'file.png';

    /** @var FileManager */
    private $fileManager;

    /** @var MockObject|GaufretteFileManager */
    private $gaufretteFileManager;

    protected function setUp(): void
    {
        $this->gaufretteFileManager = $this->createMock(GaufretteFileManager::class);
        $this->fileManager = new FileManager($this->gaufretteFileManager);
    }

    public function testGetMimeTypeFromString(): void
    {
        $mimeType = 'image/png';
        $this->gaufretteFileManager->expects($this->once())
            ->method('hasFile')
            ->with(self::FILENAME)
            ->willReturn(true);
        $this->gaufretteFileManager->expects($this->once())
            ->method('getFileMimeType')
            ->with(self::FILENAME)
            ->willReturn($mimeType);

        $this->assertEquals($mimeType, $this->fileManager->getMimeType(self::FILENAME));
    }

    public function testGetMimeTypeFromFileObject(): void
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())
            ->method('getKey')
            ->willReturn(self::FILENAME);
        $mimeType = 'image/png';
        $this->gaufretteFileManager->expects($this->once())
            ->method('hasFile')
            ->with(self::FILENAME)
            ->willReturn(true);
        $this->gaufretteFileManager->expects($this->once())
            ->method('getFileMimeType')
            ->with(self::FILENAME)
            ->willReturn($mimeType);

        $this->assertEquals($mimeType, $this->fileManager->getMimeType($file));
    }

    public function testGetMimeTypeOfNotExistedFile(): void
    {
        $this->gaufretteFileManager->expects($this->once())
            ->method('hasFile')
            ->with(self::FILENAME)
            ->willReturn(false);

        $this->assertNull($this->fileManager->getMimeType(self::FILENAME));
    }

    public function testGetMimeTypeFromStringWhenGaufretteAdapterDoesNotSupportMimeTypes(): void
    {
        $this->gaufretteFileManager->expects($this->once())
            ->method('hasFile')
            ->with(self::FILENAME)
            ->willReturn(true);
        $this->gaufretteFileManager->expects($this->once())
            ->method('getFileMimeType')
            ->with(self::FILENAME)
            ->willReturn(null);

        $this->assertNull($this->fileManager->getMimeType(self::FILENAME));
    }

    /**
     * @dataProvider fileContentDataProvider
     * @param string $fileContent
     * @param string $expectedContent
     */
    public function testSaveFileToStorage(string $fileContent, string $expectedContent)
    {
        /** @var \SplFileObject|MockObject $fileObject */
        $fileObject = $this->getMockBuilder(\SplFileObject::class)
            ->setConstructorArgs(['php://memory'])
            ->getMock();

        $fileObject
            ->expects($this->once())
            ->method('fread')
            ->willReturn($fileContent);

        /** @var \SplFileInfo|MockObject $fileInfo */
        $fileInfo = $this->getMockBuilder(\SplFileInfo::class)
            ->setConstructorArgs(['testFileName'])
            ->getMock();

        $fileInfo
            ->expects($this->once())
            ->method('openFile')
            ->willReturn($fileObject);

        $this->gaufretteFileManager->expects($this->once())
            ->method('writeToStorage')
            ->with($expectedContent, 'fileNameForSave');

        $this->fileManager->saveFileToStorage($fileInfo, 'fileNameForSave');
    }

    /**
     * @dataProvider fileContentDataProvider
     * @param string $fileContent
     * @param string $expectedContent
     */
    public function testWriteFileToStorage(string $fileContent, string $expectedContent)
    {
        $tmpFileName = $this->getTempFile('import_export_file_manager');

        file_put_contents($tmpFileName, $fileContent);

        $this->gaufretteFileManager->expects($this->once())
            ->method('writeToStorage')
            ->with($expectedContent, 'fileNameForSave');

        $this->fileManager->writeFileToStorage($tmpFileName, 'fileNameForSave');

        if (file_exists($tmpFileName)) {
            unlink($tmpFileName);
        }
    }

    public function fileContentDataProvider()
    {
        $bomBytes = pack('H*', 'EFBBBF');

        return [
            [$bomBytes . 'Col1,Col2,Col3\nVal1Val2Val3', 'Col1,Col2,Col3\nVal1Val2Val3'],
            [$bomBytes . ' some Test Content ', ' some Test Content '],
            ['Test content ' . $bomBytes, 'Test content ' . $bomBytes],
        ];
    }

    public function testGetFilesByPeriodWithDirectory()
    {
        $this->gaufretteFileManager
            ->expects(self::once())
            ->method('findFiles')
            ->willReturn(['firstDirectory']);

        $this->gaufretteFileManager
            ->expects(self::once())
            ->method('hasFile')
            ->with('firstDirectory')
            ->willReturn(false);

        self::assertEquals([], $this->fileManager->getFilesByPeriod());
    }

    public function testGetFilesByPeriodWithFile()
    {
        $this->gaufretteFileManager
            ->expects(self::once())
            ->method('findFiles')
            ->willReturn(['someFile']);

        $this->gaufretteFileManager
            ->expects(self::once())
            ->method('hasFile')
            ->with('someFile')
            ->willReturn(true);

        $filesystem = $this->createMock(FilesystemInterface::class);
        $someFile = new File('someFile', $filesystem);

        $this->gaufretteFileManager
            ->expects(self::once())
            ->method('getFile')
            ->willReturn($someFile);

        $filesystem
            ->expects(self::once())
            ->method('mtime')
            ->with('someFile')
            ->willReturn(mktime(0, 0, 0, 12, 31, 2010));

        self::assertEquals(['someFile' => $someFile], $this->fileManager->getFilesByPeriod());
    }

    /**
     * @dataProvider getFilesByPeriodDataProvider
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     */
    public function testGetFilesByPeriod(?\DateTime $from, ?\DateTime $to, array $expectedFiles)
    {
        $this->gaufretteFileManager
            ->expects(self::once())
            ->method('findFiles')
            ->willReturn(['firstFile', 'secondFile']);

        $this->gaufretteFileManager
            ->expects(self::exactly(2))
            ->method('hasFile')
            ->withConsecutive(['firstFile'], ['secondFile'])
            ->willReturn(true);

        $filesystem = $this->createMock(FilesystemInterface::class);
        $firstFile = new File('firstFile', $filesystem);
        $secondFile = new File('secondFile', $filesystem);

        $this->gaufretteFileManager
            ->expects(self::exactly(2))
            ->method('getFile')
            ->willReturnMap([
               ['firstFile', true, $firstFile],
               ['secondFile', true, $secondFile]
            ]);

        $filesystem
            ->expects(self::exactly(2))
            ->method('mtime')
            ->willReturnMap([
                ['firstFile', mktime(0, 0, 0, 12, 31, 2010)],
                ['secondFile', mktime(0, 0, 0, 12, 31, 2011)]
            ]);

        self::assertEquals($expectedFiles, array_keys($this->fileManager->getFilesByPeriod($from, $to)));
    }

    /**
     * @return array
     */
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

    public function testGetFilePath()
    {
        $fileName = 'test.txt';
        $filePath = 'gaufrette://file_system/sub_dir/test.txt';

        $this->gaufretteFileManager->expects(self::once())
            ->method('getFilePath')
            ->with($fileName)
            ->willReturn($filePath);

        self::assertEquals($filePath, $this->fileManager->getFilePath($fileName));
    }
}
