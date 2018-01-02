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

class ExportHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BatchFileManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $batchFileManager;

    /**
     * @var ReaderChain|\PHPUnit_Framework_MockObject_MockObject
     */
    private $readerChain;

    /**
     * @var WriterChain|\PHPUnit_Framework_MockObject_MockObject
     */
    private $writerChain;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configProvider;

    /**
     * @var ProcessorRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processorRegistry;

    /**
     * @var JobExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobExecutor;

    /**
     * @var FileManager|\PHPUnit_Framework_MockObject_MockObject
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

    public function testExportResultFileMergeThrowsRuntimeExceptionWhenCannotMerge()
    {
        $jobName = 'job-name';
        $processorType = 'export';
        $outputFormat = 'csv';
        $files = [];

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
