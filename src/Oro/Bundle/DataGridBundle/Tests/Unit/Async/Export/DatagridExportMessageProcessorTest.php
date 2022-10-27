<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\DataGridBundle\Async\Export\DatagridExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class DatagridExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    private ExportHandler|\PHPUnit\Framework\MockObject\MockObject $exportHandler;

    private ExportProcessor|\PHPUnit\Framework\MockObject\MockObject $exportProcessor;

    private ItemReaderInterface|\PHPUnit\Framework\MockObject\MockObject $exportItemReader;

    private WriterChain|\PHPUnit\Framework\MockObject\MockObject $writerChain;

    private FileManager|\PHPUnit\Framework\MockObject\MockObject $fileManager;

    private DatagridExportMessageProcessor $processor;

    protected function setUp(): void
    {
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->exportHandler = $this->createMock(ExportHandler::class);
        $this->exportProcessor = $this->createMock(ExportProcessor::class);
        $this->exportItemReader = $this->createMock(ItemReaderInterface::class);
        $this->writerChain = $this->createMock(WriterChain::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->processor = new DatagridExportMessageProcessor(
            $this->jobRunner,
            $this->exportHandler,
            $this->exportProcessor,
            $this->exportItemReader,
            $this->writerChain,
            $this->fileManager
        );

        $this->setUpLoggerMock($this->processor);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals(
            [DatagridExportTopic::getName()],
            DatagridExportMessageProcessor::getSubscribedTopics()
        );
    }

    public function testProcessWhenNoWriter(): void
    {
        $message = new Message();
        $messageBody = [
            'jobId' => 42,
            'contextParameters' => ['sample-key' => 'sample-value'],
            'outputFormat' => 'csv',
            'writerBatchSize' => 4242,
        ];
        $message->setBody($messageBody);

        $this->writerChain
            ->expects(self::once())
            ->method('getWriter')
            ->with($messageBody['outputFormat'])
            ->willReturn(null);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Export writer for output format {outputFormat} was expected to be {expectedClass}, got {actualClass}',
                [
                    'outputFormat' => $messageBody['outputFormat'],
                    'expectedClass' => FileStreamWriter::class,
                    'actualClass' => get_debug_type(null),
                ]
            );

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    public function testProcessWhenSuccess(): void
    {
        $message = new Message();
        $messageBody = [
            'jobId' => 42,
            'contextParameters' => ['rowsOffset' => 0, 'rowsLimit' => 4242],
            'outputFormat' => 'csv',
            'writerBatchSize' => 4242,
        ];
        $message->setBody($messageBody);

        $exportWriter = $this->createMock(FileStreamWriter::class);
        $this->writerChain
            ->expects(self::once())
            ->method('getWriter')
            ->with($messageBody['outputFormat'])
            ->willReturn($exportWriter);

        $delayedJobRunner = $this->createMock(JobRunner::class);
        $delayedJob = new Job();
        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($messageBody['jobId'], self::isType('callable'))
            ->willReturnCallback(
                function (int $jobId, callable $callback) use ($delayedJobRunner, $delayedJob) {
                    return $callback($delayedJobRunner, $delayedJob);
                }
            );

        $exportResult = [
            'success' => true,
            'readsCount' => 142,
            'errorsCount' => 0,
        ];
        $this->exportHandler
            ->expects(self::once())
            ->method('handle')
            ->with(
                $this->exportItemReader,
                $this->exportProcessor,
                $exportWriter,
                $messageBody['contextParameters'],
                $messageBody['writerBatchSize'],
                $messageBody['outputFormat']
            )
            ->willReturn($exportResult);

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Export of the batch with offset {rowsStart}, limit {rowsLimit} is finished. Success: {success}. '
                . 'Read: {readsCount}. Errors: {errorsCount}',
                [
                    'rowsOffset' => $messageBody['contextParameters']['rowsOffset'],
                    'rowsLimit' => $messageBody['contextParameters']['rowsLimit'],
                    'success' => 'Yes',
                    'readsCount' => $exportResult['readsCount'],
                    'errorsCount' => $exportResult['errorsCount'],
                ]
            );

        $this->fileManager
            ->expects(self::never())
            ->method('writeToStorage');

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );

        self::assertEquals($exportResult, $delayedJob->getData());
    }

    public function testProcessWhenFailure(): void
    {
        $message = new Message();
        $messageBody = [
            'jobId' => 42,
            'contextParameters' => ['rowsOffset' => 0, 'rowsLimit' => 4242],
            'outputFormat' => 'csv',
            'writerBatchSize' => 4242,
        ];
        $message->setBody($messageBody);

        $exportWriter = $this->createMock(FileStreamWriter::class);
        $this->writerChain
            ->expects(self::once())
            ->method('getWriter')
            ->with($messageBody['outputFormat'])
            ->willReturn($exportWriter);

        $delayedJobRunner = $this->createMock(JobRunner::class);
        $delayedJob = new Job();
        $this->jobRunner
            ->expects(self::once())
            ->method('runDelayed')
            ->with($messageBody['jobId'], self::isType('callable'))
            ->willReturnCallback(
                function (int $jobId, callable $callback) use ($delayedJobRunner, $delayedJob) {
                    return $callback($delayedJobRunner, $delayedJob);
                }
            );

        $exportResult = [
            'success' => false,
            'readsCount' => 142,
            'errorsCount' => 1,
            'errors' => ['Sample error 1'],
        ];
        $this->exportHandler
            ->expects(self::once())
            ->method('handle')
            ->with(
                $this->exportItemReader,
                $this->exportProcessor,
                $exportWriter,
                $messageBody['contextParameters'],
                $messageBody['writerBatchSize'],
                $messageBody['outputFormat']
            )
            ->willReturn($exportResult);

        $this->loggerMock
            ->expects(self::once())
            ->method('info')
            ->with(
                'Export of the batch with offset {rowsStart}, limit {rowsLimit} is finished. Success: {success}. '
                . 'Read: {readsCount}. Errors: {errorsCount}',
                [
                    'rowsOffset' => $messageBody['contextParameters']['rowsOffset'],
                    'rowsLimit' => $messageBody['contextParameters']['rowsLimit'],
                    'success' => 'No',
                    'readsCount' => $exportResult['readsCount'],
                    'errorsCount' => $exportResult['errorsCount'],
                ]
            );

        $this->fileManager
            ->expects(self::once())
            ->method('writeToStorage')
            ->with(
                json_encode($exportResult['errors'], JSON_THROW_ON_ERROR),
                self::matchesRegularExpression('/^export.+?\.json$/')
            )
            ->willReturnCallback(function (string $data, string $filename) use (&$exportResult) {
                $exportResult['errorLogFile'] = $filename;
            });

        self::assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );

        self::assertEquals($exportResult, $delayedJob->getData());
    }
}
