<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\File\BatchFileManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;

class BatchFileManagerTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldSplitFile()
    {
        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects($this->once())
            ->method('initializeByContext')
            ->with(new Context([
                Context::OPTION_FILE_PATH => 'test.csv',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_ENCLOSURE => '|',
            ]));

        $reader->expects($this->exactly(3))
            ->method('read')
            ->with(new Context([
                Context::OPTION_FILE_PATH => 'test.csv',
                Context::OPTION_DELIMITER => ';',
                Context::OPTION_ENCLOSURE => '|',
            ]))
            ->willReturnOnConsecutiveCalls([1, 2], [3, 4], false);
        $reader->expects($this->once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);

        $fileManagerMock = $this->createMock(FileManager::class);
        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects($this->exactly(2))
            ->method('setImportExportContext');

        $writer->expects($this->exactly(2))
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
        $this->assertCount(2, $batchFileManager->splitFile('test.csv'));
    }

    public function testShouldSplitFileWithCustomBatchSize(): void
    {
        $context = new Context([
            Context::OPTION_FILE_PATH => 'test.csv',
            Context::OPTION_DELIMITER => ';',
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_BATCH_SIZE => 3,
        ]);

        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects($this->once())
            ->method('initializeByContext')
            ->with($context);
        $reader->expects($this->exactly(6))
            ->method('read')
            ->with($context)
            ->willReturnOnConsecutiveCalls([1, 2], [3, 4], [5, 6], [7, 8], [9, 10], false);
        $reader->expects($this->once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);

        $fileManagerMock = $this->createMock(FileManager::class);

        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects($this->exactly(2))
            ->method('setImportExportContext');

        $writer->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [[[1, 2], [3, 4], [5, 6]]],
                [[[7, 8], [9, 10]]]
            );
        $batchFileManager = new BatchFileManager($fileManagerMock, 1);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);
        $batchFileManager->setConfigurationOptions([
            Context::OPTION_DELIMITER => ';',
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_BATCH_SIZE => 3,
        ]);
        $this->assertCount(2, $batchFileManager->splitFile('test.csv'));
    }

    public function testShouldThrowErrorDuringSplitFile()
    {
        $fileManagerMock = $this->createMock(FileManager::class);
        $batchFileManager = new BatchFileManager($fileManagerMock, 1);
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager->splitFile('test.csv');
    }

    public function testShouldThrowErrorDuringMergeFiles()
    {
        $fileManagerMock = $this->createMock(FileManager::class);
        $batchFileManager = new BatchFileManager($fileManagerMock, 1);
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager->splitFile('test.csv');
    }

    public function testShouldMergeFiles()
    {
        $fileManagerMock = $this->createMock(FileManager::class);

        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects($this->exactly(2))
            ->method('initializeByContext')
            ->withConsecutive(
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test2'])]
            );
        $reader->expects($this->exactly(5))
            ->method('read')
            ->withConsecutive(
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test2'])],
                [new Context(['filePath' => 'test2'])]
            )
            ->willReturnOnConsecutiveCalls(1, 2, false, 3, false);
        $reader->expects($this->once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);

        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects($this->once())
            ->method('setImportExportContext')
            ->with(new Context(['filePath' => 'result', 'header' => ['a', 'b'], 'firstLineIsHeader' => true]));
        $writer->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [[1, 2]],
                [[3]]
            );
        $writer->expects($this->once())
            ->method('close');

        $batchFileManager = new BatchFileManager($fileManagerMock, 10);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);

        $this->assertEquals('result', $batchFileManager->mergeFiles(['test1', 'test2'], 'result'));
    }

    public function testShouldMergeFilesWhenBatchSizeIsExceeded()
    {
        $fileManagerMock = $this->createMock(FileManager::class);

        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects($this->exactly(2))
            ->method('initializeByContext')
            ->withConsecutive(
                [new Context(['filePath' => 'test1'])],
                [new Context(['filePath' => 'test2'])]
            );
        $reader->expects($this->exactly(6))
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
        $reader->expects($this->once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);

        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects($this->once())
            ->method('setImportExportContext')
            ->with(new Context(['filePath' => 'result', 'header' => ['a', 'b'], 'firstLineIsHeader' => true]));
        $writer->expects($this->exactly(3))
            ->method('write')
            ->withConsecutive(
                [[1, 2]],
                [[3]],
                [[4]]
            );
        $writer->expects($this->once())
            ->method('close');

        $batchFileManager = new BatchFileManager($fileManagerMock, 2);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);

        $this->assertEquals('result', $batchFileManager->mergeFiles(['test1', 'test2'], 'result'));
    }
}
