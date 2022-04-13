<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topic\DatagridExportTopic;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ExportMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldReturnSubscribedTopics(): void
    {
        self::assertEquals(
            [DatagridExportTopic::getName()],
            ExportMessageProcessor::getSubscribedTopics()
        );
    }

    public function testShouldRejectMessageAndLogCriticalIfInvalidWriter(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('critical')
            ->with('Invalid format: "invalid_format"');

        $writerChain = $this->createMock(WriterChain::class);
        $writerChain->expects(self::once())
            ->method('getWriter')
            ->with('invalid_format')
            ->willReturn(null);

        $processor = new ExportMessageProcessor(
            $this->createMock(JobRunner::class),
            $this->createMock(FileManager::class),
            $logger
        );
        $processor->setWriterChain($writerChain);

        $message = new Message();
        $message->setBody(JSON::encode([
            'jobId' => 1,
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'invalid_format',
        ]));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessExportAndReturnACK(): void
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0');

        $job = new Job();

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects(self::once())
            ->method('runDelayed')
            ->with(1)
            ->willReturnCallback(function ($jobId, $callback) use ($jobRunner, $job) {
                return $callback($jobRunner, $job);
            });

        $fileStreamWriter = $this->createMock(FileStreamWriter::class);

        $writerChain = $this->createMock(WriterChain::class);
        $writerChain->expects(self::once())
            ->method('getWriter')
            ->with('csv')
            ->willReturn($fileStreamWriter);

        $connector = $this->createMock(DatagridExportConnector::class);
        $exportProcessor = $this->createMock(ExportProcessor::class);

        $processor = new ExportMessageProcessor(
            $jobRunner,
            $this->createMock(FileManager::class),
            $logger
        );
        $processor->setWriterChain($writerChain);
        $processor->setExportProcessor($exportProcessor);
        $processor->setExportConnector($connector);

        $exportHandler = $this->createMock(ExportHandler::class);
        $exportHandler->expects(self::once())
            ->method('handle')
            ->with(
                $connector,
                $exportProcessor,
                $fileStreamWriter,
                [
                    'gridName' => 'grid_name',
                    'gridParameters' => new ParameterBag([
                        '_datagrid_modes' => ['importexport']
                    ]),
                    'format_type' => 'excel',
                    'pageSize' => 5000
                ],
                5000,
                'csv'
            )
            ->willReturn($exportResult);
        $processor->setExportHandler($exportHandler);

        $message = new Message();
        $message->setBody(JSON::encode([
            'jobId' => 1,
            'parameters' => [
                'gridName' => 'grid_name',
                'gridParameters' => [],
                'format_type' => 'excel',
                'pageSize' => 5000,
            ],
            'format' => 'csv',
            'batchSize' => 5000
        ]));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
