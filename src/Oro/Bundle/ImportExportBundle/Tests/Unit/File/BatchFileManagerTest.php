<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\File\BatchFileManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use PHPUnit\Framework\TestCase;

class BatchFileManagerTest extends TestCase
{
    public function testSplitFileWhenReaderAndWriterAreNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 1);
        $batchFileManager->splitFile('test.csv');
    }

    public function testSplitFileWhenReaderIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 1);
        $batchFileManager->setWriter($this->createMock(FileStreamWriter::class));
        $batchFileManager->splitFile('test.csv');
    }

    public function testSplitFileWhenWriterIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 1);
        $batchFileManager->setReader($this->createMock(AbstractFileReader::class));
        $batchFileManager->splitFile('test.csv');
    }

    public function testSplitFile(): void
    {
        $readerContext = new Context([
            Context::OPTION_FILE_PATH => 'test.csv',
            Context::OPTION_DELIMITER => ';',
            Context::OPTION_ENCLOSURE => '|'
        ]);
        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects(self::once())
            ->method('initializeByContext')
            ->with($readerContext);
        $reader->expects(self::exactly(3))
            ->method('read')
            ->with($readerContext)
            ->willReturnOnConsecutiveCalls(
                [1, 2],
                [3, 4],
                false
            );
        $reader->expects(self::once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);
        $reader->expects(self::once())
            ->method('close');

        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects(self::exactly(2))
            ->method('setImportExportContext');
        $writer->expects(self::exactly(2))
            ->method('write')
            ->withConsecutive(
                [[[1, 2]]],
                [[[3, 4]]]
            );

        $tmpFile1Path = '/tmp/test1';
        $tmpFile2Path = '/tmp/test2';
        $fileManager = $this->createMock(FileManager::class);
        $fileManager->expects(self::exactly(2))
            ->method('createTmpFile')
            ->willReturnOnConsecutiveCalls($tmpFile1Path, $tmpFile2Path);
        $fileManager->expects(self::exactly(2))
            ->method('writeFileToStorage')
            ->withConsecutive(
                [$tmpFile1Path, self::matches('%s.csv')],
                [$tmpFile2Path, self::matches('%s.csv')]
            );
        $fileManager->expects(self::exactly(2))
            ->method('deleteTmpFile')
            ->withConsecutive([$tmpFile1Path], [$tmpFile2Path]);

        $batchFileManager = new BatchFileManager($fileManager, 1);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);
        $batchFileManager->setConfigurationOptions([
            Context::OPTION_DELIMITER => ';',
            Context::OPTION_ENCLOSURE => '|'
        ]);
        self::assertCount(2, $batchFileManager->splitFile('test.csv'));
    }

    public function testSplitFileWithCustomBatchSize(): void
    {
        $readerContext = new Context([
            Context::OPTION_FILE_PATH => 'test.csv',
            Context::OPTION_DELIMITER => ';',
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_BATCH_SIZE => 3
        ]);
        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects(self::once())
            ->method('initializeByContext')
            ->with($readerContext);
        $reader->expects(self::exactly(6))
            ->method('read')
            ->with($readerContext)
            ->willReturnOnConsecutiveCalls(
                [1, 2],
                [3, 4],
                [5, 6],
                [7, 8],
                [9, 10],
                false
            );
        $reader->expects(self::once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);
        $reader->expects(self::once())
            ->method('close');

        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects(self::exactly(2))
            ->method('setImportExportContext');
        $writer->expects(self::exactly(2))
            ->method('write')
            ->withConsecutive(
                [[[1, 2], [3, 4], [5, 6]]],
                [[[7, 8], [9, 10]]]
            );

        $tmpFile1Path = '/tmp/test1';
        $tmpFile2Path = '/tmp/test2';
        $fileManager = $this->createMock(FileManager::class);
        $fileManager->expects(self::exactly(2))
            ->method('createTmpFile')
            ->willReturnOnConsecutiveCalls($tmpFile1Path, $tmpFile2Path);
        $fileManager->expects(self::exactly(2))
            ->method('writeFileToStorage')
            ->withConsecutive(
                [$tmpFile1Path, self::matches('%s.csv')],
                [$tmpFile2Path, self::matches('%s.csv')]
            );
        $fileManager->expects(self::exactly(2))
            ->method('deleteTmpFile')
            ->withConsecutive([$tmpFile1Path], [$tmpFile2Path]);

        $batchFileManager = new BatchFileManager($fileManager, 1);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);
        $batchFileManager->setConfigurationOptions([
            Context::OPTION_DELIMITER => ';',
            Context::OPTION_ENCLOSURE => '|',
            Context::OPTION_BATCH_SIZE => 3
        ]);
        self::assertCount(2, $batchFileManager->splitFile('test.csv'));
    }

    public function testMergeFilesWhenReaderAndWriterAreNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 1);
        $batchFileManager->mergeFiles(['test1', 'test2'], 'result');
    }

    public function testMergeFilesWhenReaderIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 1);
        $batchFileManager->setWriter($this->createMock(FileStreamWriter::class));
        $batchFileManager->mergeFiles(['test1', 'test2'], 'result');
    }

    public function testMergeFilesWhenWriterIsNotSet(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Reader and Writer must be configured.');
        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 1);
        $batchFileManager->setReader($this->createMock(AbstractFileReader::class));
        $batchFileManager->mergeFiles(['test1', 'test2'], 'result');
    }

    public function testMergeFiles(): void
    {
        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects(self::exactly(2))
            ->method('initializeByContext')
            ->withConsecutive(
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test2'])]
            );
        $reader->expects(self::exactly(5))
            ->method('read')
            ->withConsecutive(
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test2'])],
                [new Context([Context::OPTION_FILE_PATH => 'test2'])]
            )
            ->willReturnOnConsecutiveCalls(
                1,
                2,
                false,
                3,
                false
            );
        $reader->expects(self::once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);
        $reader->expects(self::exactly(2))
            ->method('close');

        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects(self::once())
            ->method('setImportExportContext')
            ->with(new Context([
                Context::OPTION_FILE_PATH => 'result',
                Context::OPTION_HEADER => ['a', 'b'],
                Context::OPTION_FIRST_LINE_IS_HEADER => true
            ]));
        $writer->expects(self::exactly(2))
            ->method('write')
            ->withConsecutive(
                [[1, 2]],
                [[3]]
            );
        $writer->expects(self::once())
            ->method('close');

        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 10);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);
        $batchFileManager->mergeFiles(['test1', 'test2'], 'result');
    }

    public function testMergeFilesWhenBatchSizeIsExceeded(): void
    {
        $reader = $this->createMock(AbstractFileReader::class);
        $reader->expects(self::exactly(2))
            ->method('initializeByContext')
            ->withConsecutive(
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test2'])]
            );
        $reader->expects(self::exactly(6))
            ->method('read')
            ->withConsecutive(
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test1'])],
                [new Context([Context::OPTION_FILE_PATH => 'test2'])],
                [new Context([Context::OPTION_FILE_PATH => 'test2'])]
            )
            ->willReturnOnConsecutiveCalls(
                1,
                2,
                3,
                false,
                4,
                false
            );
        $reader->expects(self::once())
            ->method('getHeader')
            ->willReturn(['a', 'b']);
        $reader->expects(self::exactly(2))
            ->method('close');

        $writer = $this->createMock(FileStreamWriter::class);
        $writer->expects(self::once())
            ->method('setImportExportContext')
            ->with(new Context([
                Context::OPTION_FILE_PATH => 'result',
                Context::OPTION_HEADER => ['a', 'b'],
                Context::OPTION_FIRST_LINE_IS_HEADER => true
            ]));
        $writer->expects(self::exactly(3))
            ->method('write')
            ->withConsecutive(
                [[1, 2]],
                [[3]],
                [[4]]
            );
        $writer->expects(self::once())
            ->method('close');

        $batchFileManager = new BatchFileManager($this->createMock(FileManager::class), 2);
        $batchFileManager->setReader($reader);
        $batchFileManager->setWriter($writer);
        $batchFileManager->mergeFiles(['test1', 'test2'], 'result');
    }
}
