<?php

namespace Oro\Bundle\ImportExportBundle\File;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;

class BatchFileManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSplitFile()
    {
        $reader = $this->createReaderMock();
        $reader
            ->expects($this->once())
            ->method('initializeByContext')
            ->with(new Context([
                Context::OPTION_FILE_PATH => 'test.csv',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_ENCLOSURE => '|',
            ]));

        $reader
            ->expects($this->exactly(3))
            ->method('read')
            ->with(new Context([
                Context::OPTION_FILE_PATH => 'test.csv',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_ENCLOSURE => '|',
            ]))
            ->willReturnOnConsecutiveCalls([1, 2], [3, 4], false);
        $reader
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);

        $fileManagerMock = $this->createFileManagerMock();
        $writer = $this->createWriterMock();
        $writer
            ->expects($this->exactly(2))
            ->method('setImportExportContext')
        ;

        $writer
            ->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [[[1, 2]]],
                [[[3, 4]]]
            );
        $batchFileManager = new BatchFileManager($fileManagerMock, 1);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);
        $batchFileManager->setConfigurationOptions([
            Context::OPTION_DELIMITER => ';',
            Context::OPTION_ENCLOSURE => '|',
        ]);
        $splittedFiles = $batchFileManager->splitFile('test.csv');
        $this->assertCount(2, $splittedFiles);
    }

    public function testShouldThrowErrorDuringSplitFile()
    {
        $fileManagerMock = $this->createFileManagerMock();
        $batchFileManager = new BatchFileManager($fileManagerMock, 1);
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager->splitFile('test.csv');
    }

    public function testShouldThrowErrorDuringMergeFiles()
    {
        $fileManagerMock = $this->createFileManagerMock();
        $batchFileManager = new BatchFileManager($fileManagerMock, 1);
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager->splitFile('test.csv');
    }

    public function testShouldMergeFiles()
    {
        $fileManagerMock = $this->createFileManagerMock();

        $reader = $this->createReaderMock();
        $reader
            ->expects($this->exactly(2))
            ->method('initializeByContext')
            ->withConsecutive(
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test2'])]
            );
        $reader
            ->expects($this->exactly(5))
            ->method('read')
            ->withConsecutive(
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test2'])],
                [new Context(['filePath' => 'test2'])]
            )
            ->willReturnOnConsecutiveCalls(1, 2, false, 3, false);
        $reader
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);

        $writer = $this->createWriterMock();
        $writer
            ->expects($this->once())
            ->method('setImportExportContext')
            ->with(new Context(['filePath' => 'result', 'header' => ['a', 'b'], 'firstLineIsHeader' => true]));
        $writer
            ->expects($this->at(1))
            ->method('write')
            ->with([1, 2]);
        $writer
            ->expects($this->at(2))
            ->method('write')
            ->with([3]);
        $writer
            ->expects($this->once())
            ->method('close');

        $batchFileManager = new BatchFileManager($fileManagerMock, 10);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);

        $this->assertEquals($batchFileManager->mergeFiles(['test1', 'test2'], 'result'), 'result');
    }

    public function testShouldMergeFilesWhenBatchSizeIsExceeded()
    {
        $fileManagerMock = $this->createFileManagerMock();

        $reader = $this->createReaderMock();
        $reader
            ->expects($this->exactly(2))
            ->method('initializeByContext')
            ->withConsecutive(
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test2'])]
            );
        $reader
            ->expects($this->exactly(6))
            ->method('read')
            ->withConsecutive(
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test2'])],
                [new Context(['filePath' => 'test2'])]
            )
            ->willReturnOnConsecutiveCalls(1, 2, 3, false, 4, false);
        $reader
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);

        $writer = $this->createWriterMock();
        $writer
            ->expects($this->once())
            ->method('setImportExportContext')
            ->with(new Context(['filePath' => 'result', 'header' => ['a', 'b'], 'firstLineIsHeader' => true]));
        $writer
            ->expects($this->at(1))
            ->method('write')
            ->with([1, 2]);
        $writer
            ->expects($this->at(2))
            ->method('write')
            ->with([3]);
        $writer
            ->expects($this->at(3))
            ->method('write')
            ->with([4]);
        $writer
            ->expects($this->once())
            ->method('close');

        $batchFileManager = new BatchFileManager($fileManagerMock, 2);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);

        $this->assertEquals($batchFileManager->mergeFiles(['test1', 'test2'], 'result'), 'result');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AbstractFileReader
     */
    private function createReaderMock()
    {
        return $this->createMock(AbstractFileReader::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FileStreamWriter
     */
    private function createWriterMock()
    {
        return $this->createMock(FileStreamWriter::class);
    }
    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FileManager
     */
    private function createFileManagerMock()
    {
        return $this->createMock(FileManager::class);
    }
}
