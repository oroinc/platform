<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Gaufrette\File;
use Gaufrette\Filesystem;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FileManagerTest extends TestCase
{
    use TempDirExtension;

    const FILENAME = 'file.png';

    /** @var FileManager */
    private $fileManager;

    /** @var MockObject|FileSystem */
    private $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->fileManager = new FileManager(new FilesystemMap(['importexport' => $this->filesystem]));
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
            ->willThrowException(new \LogicException('no mime type.'));
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
        $tmpFileName = $this->getTempFile('import_export_file_manager');

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

    public function testGetFilesByPeriodWithDirectory()
    {
        $this->filesystem
            ->expects(self::once())
            ->method('keys')
            ->willReturn(['firstDirectory']);

        $this->filesystem
            ->expects(self::once())
            ->method('has')
            ->with('firstDirectory')
            ->willReturn(false);

        self::assertEquals([], $this->fileManager->getFilesByPeriod());
    }

    public function testGetFilesByPeriodWithFile()
    {
        $this->filesystem
            ->expects(self::once())
            ->method('keys')
            ->willReturn(['someFile']);

        $this->filesystem
            ->expects(self::once())
            ->method('has')
            ->with('someFile')
            ->willReturn(true);

        $someFile = new File('someFile', $this->filesystem);

        $this->filesystem
            ->expects(self::once())
            ->method('get')
            ->willReturn($someFile);

        $this->filesystem
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
        $this->filesystem
            ->expects(self::once())
            ->method('keys')
            ->willReturn(['firstFile', 'secondFile']);

        $this->filesystem
            ->expects(self::exactly(2))
            ->method('has')
            ->withConsecutive(['firstFile'], ['secondFile'])
            ->willReturn(true);

        $firstFile = new File('firstFile', $this->filesystem);
        $secondFile = new File('secondFile', $this->filesystem);

        $this->filesystem
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
               ['firstFile', false, $firstFile],
               ['secondFile', false, $secondFile]
            ]);

        $this->filesystem
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
}
