<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Handler;

use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Filesystem;
use Gaufrette\StreamWrapper;
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
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportHandlerTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var BatchFileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $batchFileManager;

    /** @var ReaderChain|\PHPUnit\Framework\MockObject\MockObject */
    private $readerChain;

    /** @var WriterChain|\PHPUnit\Framework\MockObject\MockObject */
    private $writerChain;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var JobExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $jobExecutor;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var ExportHandler */
    private $exportHandler;

    /** @var string */
    private $directory;

    /** @var Filesystem */
    private $filesystem;

    protected function setUp(): void
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

        $this->directory = $this->getTempDir('ExportHandler');
        file_put_contents($this->directory . DIRECTORY_SEPARATOR . 'test1.csv', '1,test,test2;');

        $this->filesystem = new Filesystem(new LocalAdapter($this->directory));
        $this->registerLocalFilesystemInStream();
    }

    protected function tearDown(): void
    {
        @unlink($this->directory . DIRECTORY_SEPARATOR . 'test1.csv');
        @rmdir($this->directory);
    }

    private function registerLocalFilesystemInStream()
    {
        $filesystemMap = StreamWrapper::getFilesystemMap();
        $filesystemMap->set('importexport', $this->filesystem);
        StreamWrapper::register();
    }

    public function testHandleDownloadExportResult()
    {
        $fileName = 'test1.csv';
        $filePath = 'gaufrette://importexport/test1.csv';
        $this->fileManager->expects($this->once())
            ->method('isFileExist')
            ->with($fileName)
            ->willReturn(true);
        $this->fileManager->expects($this->once())
            ->method('getFilePath')
            ->with($fileName)
            ->willReturn($filePath);
        $this->fileManager->expects($this->once())
            ->method('getMimeType')
            ->willReturn('text/csv');

        $response = $this->exportHandler->handleDownloadExportResult($fileName);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals('attachment; filename=test1.csv', $response->headers->get('Content-Disposition'));
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        $this->assertStringEqualsFile($this->directory . DIRECTORY_SEPARATOR . $fileName, $content);
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
