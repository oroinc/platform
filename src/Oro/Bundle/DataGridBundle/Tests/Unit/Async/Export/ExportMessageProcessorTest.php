<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Async\Export;

use Oro\Bundle\DataGridBundle\Async\Export\ExportMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\Topics;
use Oro\Bundle\DataGridBundle\Handler\ExportHandler;
use Oro\Bundle\DataGridBundle\ImportExport\DatagridExportConnector;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor;
use Oro\Bundle\ImportExportBundle\Writer\FileStreamWriter;
use Oro\Bundle\ImportExportBundle\Writer\WriterChain;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT],
            ExportMessageProcessor::getSubscribedTopics()
        );
    }

    public function invalidMessageProvider()
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
     *
     * @param string $loggerMessage
     * @param array $messageBody
     */
    public function testShouldRejectMessageAndLogCriticalIfInvalidMessage($loggerMessage, $messageBody)
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->equalTo($loggerMessage))
        ;

        $processor = new ExportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createJobStorageMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode($messageBody));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfInvalidWriter()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with($this->equalTo('Invalid format: "invalid_format"'))
        ;

        $writerChain = $this->createWriterChainMock();
        $writerChain
            ->expects($this->once())
            ->method('getWriter')
            ->with($this->equalTo('invalid_format'))
            ->willReturn(null)
        ;

        $processor = new ExportMessageProcessor(
            $this->createJobRunnerMock(),
            $this->createJobStorageMock(),
            $logger
        );
        $processor->setWriterChain($writerChain);

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobId' => 1,
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'invalid_format',
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::REJECT, $result);
    }

    public function testShouldProcessExportAndReturnACK()
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0'))
        ;

        $job = new Job();

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with($this->equalTo(1))
            ->will($this->returnCallback(function ($jobId, $callback) use ($jobRunner, $job) {
                return $callback($jobRunner, $job);
            }))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
        ;

        $fileStreamWriter = $this->createFileStreamWriterMock();

        $writerChain = $this->createWriterChainMock();
        $writerChain
            ->expects($this->once())
            ->method('getWriter')
            ->with($this->equalTo('csv'))
            ->willReturn($fileStreamWriter)
        ;

        $exportHandler = $this->createExportHandlerMock();
        $exportHandler
            ->expects($this->once())
            ->method('handle')
            ->willReturn($exportResult)
        ;

        $processor = new ExportMessageProcessor(
            $jobRunner,
            $jobStorage,
            $logger
        );
        $processor->setWriterChain($writerChain);
        $processor->setExportHandler($exportHandler);
        $processor->setExportProcessor($this->createExportProcessorMock());
        $processor->setExportConnector($this->createExportConnectorMock());

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobId' => 1,
            'parameters' => ['gridName' => 'grid_name'],
            'format' => 'csv',
        ]));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessor::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WriterChain
     */
    private function createWriterChainMock()
    {
        return $this->createMock(WriterChain::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FileStreamWriter
     */
    private function createFileStreamWriterMock()
    {
        return $this->createMock(FileStreamWriter::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->createMock(ExportHandler::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportProcessor
     */
    private function createExportProcessorMock()
    {
        return $this->createMock(ExportProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridExportConnector
     */
    private function createExportConnectorMock()
    {
        return $this->createMock(DatagridExportConnector::class);
    }
}
