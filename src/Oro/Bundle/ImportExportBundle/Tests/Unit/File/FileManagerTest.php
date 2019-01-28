<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\SEOBundle\Sitemap\Exception\LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileManagerTest extends TestCase
{
    const FILENAME = 'file.png';

    /** @var FileManager */
    private $fileManager;

    /** @var MockObject|FileSystem */
    private $filesystem;

    public function setUp()
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $filesystemMap = $this->createMock(FilesystemMap::class);
        $filesystemMap->expects($this->once())
            ->method('get')
            ->with('importexport')
            ->willReturn($this->filesystem);

        $this->fileManager = new FileManager($filesystemMap);
    }

    public function testGetMimeTypeFromString(): void
    {
        $mimeType = 'image/png';
        $this->filesystem->expects($this->once())
            ->method('has')
            ->with(self::FILENAME)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('mimeType')
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
        $this->filesystem->expects($this->once())
            ->method('has')
            ->with(self::FILENAME)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('mimeType')
            ->with(self::FILENAME)
            ->willReturn($mimeType);

        $this->assertEquals($mimeType, $this->fileManager->getMimeType($file));
    }

    public function testGetMimeTypeOfNotExistedFile(): void
    {
        $this->filesystem->expects($this->once())
            ->method('has')
            ->with(self::FILENAME)
            ->willReturn(false);

        $this->assertNull($this->fileManager->getMimeType(self::FILENAME));
    }

    public function testGetMimeTypeFromStringWithoutMimetypeAdapter(): void
    {
        $this->filesystem->expects($this->once())
            ->method('has')
            ->with(self::FILENAME)
            ->willReturn(true);
        $this->filesystem->expects($this->once())
            ->method('mimeType')
            ->with(self::FILENAME)
            ->willThrowException(new LogicException('no mime type.'));
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

        $this->filesystem->expects($this->once())
            ->method('write')
            ->with('fileNameForSave', $expectedContent, false);

        $this->fileManager->saveFileToStorage($fileInfo, 'fileNameForSave', false);
    }

    /**
     * @dataProvider fileContentDataProvider
     * @param string $fileContent
     * @param string $expectedContent
     */
    public function testWriteFileToStorage(string $fileContent, string $expectedContent)
    {
        $tmpFileName = tempnam(sys_get_temp_dir(), 'import_export_file_manager');

        file_put_contents($tmpFileName, $fileContent);

        $this->filesystem->expects($this->once())
            ->method('write')
            ->with('fileNameForSave', $expectedContent, false);

        $this->fileManager->writeFileToStorage($tmpFileName, 'fileNameForSave', false);

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
}
