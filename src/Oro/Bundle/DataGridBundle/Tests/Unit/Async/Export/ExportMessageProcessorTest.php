<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;
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
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT],
            ExportMessageProcessor::getSubscribedTopics()
        );
    }

    public function invalidMessageProvider(): array
    {
        return [
            [
                'Got invalid message',
                ['parameters' => ['gridName' => 'grid_name'], 'format' => 'csv'],
            ],
            [
                'Got invalid message',
                ['jobId' => 1, 'format' => 'csv'],
            ],
            [
                'Got invalid message',
                ['jobId' => 1, 'parameters' => ['gridName' => 'grid_name']],
            ],
        ];
    }

    /**
     * @dataProvider invalidMessageProvider
     */
    public function testShouldRejectMessageAndLogCriticalIfInvalidMessage(string $loggerMessage, array $messageBody)
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with($loggerMessage);

        $processor = new ExportMessageProcessor(
            $this->createMock(JobRunner::class),
            $this->createMock(FileManager::class),
            $logger
        );

        $message = new Message();
        $message->setBody(JSON::encode($messageBody));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfInvalidWriter()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Invalid format: "invalid_format"');

        $writerChain = $this->createMock(WriterChain::class);
        $writerChain->expects($this->once())
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

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldProcessExportAndReturnACK()
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0');

        $job = new Job();

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runDelayed')
            ->with(1)
            ->willReturnCallback(function ($jobId, $callback) use ($jobRunner, $job) {
                return $callback($jobRunner, $job);
            });

        $fileStreamWriter = $this->createMock(FileStreamWriter::class);

        $writerChain = $this->createMock(WriterChain::class);
        $writerChain->expects($this->once())
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
        $exportHandler->expects($this->once())
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
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv',
            'batchSize' => 5000
        ]));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
