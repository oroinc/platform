<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Handler;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\File\BatchFileManager;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Reader\AbstractFileReader;
use Oro\Bundle\ImportExportBundle\Reader\ReaderChain;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Symfony\Component\Translation\TranslatorInterface;

class ExportHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BatchFileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $batchFileManager;

    /**
     * @var ReaderChain|\PHPUnit\Framework\MockObject\MockObject
     */
    private $readerChain;

    /**
     * @var WriterChain|\PHPUnit\Framework\MockObject\MockObject
     */
    private $writerChain;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configProvider;

    /**
     * @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $processorRegistry;

    /**
     * @var JobExecutor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobExecutor;

    /**
     * @var FileManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fileManager;

    /**
     * @var ExportHandler
     */
    private $exportHandler;


    protected function setUp()
    {
        $this->jobExecutor = $this->createMock(JobExecutor::class);
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->writerChain = $this->createMock(WriterChain::class);
        $this->readerChain = $this->createMock(ReaderChain::class);
        $this->batchFileManager = $this->createMock(BatchFileManager::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->exportHandler = new ExportHandler(
            $this->jobExecutor,
            $this->processorRegistry,
            $this->configProvider,
            $this->translator,
            $this->writerChain,
            $this->readerChain,
            $this->batchFileManager,
            $this->fileManager
        );
    }

    public function testHandleDownloadExportResult()
    {
        $fileContent = '1,test,test2;';
        $this->fileManager->expects($this->once())
            ->method('getContent')
            ->willReturn($fileContent);
        $this->fileManager->expects($this->once())
            ->method('getMimeType')
            ->willReturn('text/csv');
        $response = $this->exportHandler->handleDownloadExportResult('test1.csv');
        $this->assertEquals($fileContent, $response->getContent());
        $this->assertEquals('attachment; filename="test1.csv"', $response->headers->get('Content-Disposition'));
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
    }

    public function testExportResultFileMergeThrowsRuntimeExceptionWhenCannotMerge()
    {
        $jobName = 'job-name';
        $processorType = 'export';
        $outputFormat = 'csv';
        $files = ['test1.csv', 'test2.csv'];

        $writer = $this->createMock(FileStreamWriter::class);
        $this->writerChain
            ->expects(self::once())
            ->method('getWriter')
            ->willReturn($writer);

        $reader = $this->createMock(AbstractFileReader::class);
        $this->readerChain
            ->expects(self::once())
            ->method('getReader')
            ->willReturn($reader);

        $this->fileManager->expects($this->at(0))
            ->method('writeToTmpLocalStorage')
            ->with('test1.csv')
            ->willReturn('test1.csv');

        $this->fileManager->expects($this->at(1))
            ->method('fixNewLines')
            ->with('test1.csv')
            ->willReturn('test1.csv');

        $this->fileManager->expects($this->at(2))
            ->method('writeToTmpLocalStorage')
            ->with('test2.csv')
            ->willReturn('test2.csv');

        $this->fileManager->expects($this->at(3))
            ->method('fixNewLines')
            ->with('test2.csv')
            ->willReturn('test2.csv');

        $exceptionMessage = 'Exception message';
        $exception = new \Exception($exceptionMessage);
        $this->batchFileManager
            ->expects(self::once())
            ->method('mergeFiles')
            ->willThrowException($exception);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(sprintf('Cannot merge export files into single summary file'));

        $this->exportHandler->exportResultFileMerge($jobName, $processorType, $outputFormat, $files);
    }
}
