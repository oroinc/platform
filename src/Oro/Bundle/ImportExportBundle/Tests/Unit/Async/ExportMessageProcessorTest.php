<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ImportExportBundle\Async\ExportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ExportMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerInterfaceMock()
        );
    }

    public function testShouldRejectMessageAndLogCriticalIfJobNameIsMissing()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Got invalid message: "{"processorAlias":"alias"}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'processorAlias' => 'alias',
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogCriticalIfProcessorAliasIsMissing()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[ExportMessageProcessor] Got invalid message: "{"jobName":"name"}"')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode([
            'jobName' => 'name',
        ]));

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunUniqueJobAndRejectOnFailedExport()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $message = new NullMessage();
        $message->setMessageId(123);
        $message->setBody(json_encode([
            'jobName' => 'name',
            'processorAlias' => 'alias',
        ]));

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(123, Topics::EXPORT .'_alias')
            ->willReturn(false)
        ;

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $jobRunner,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRunUniqueJobAndAckOnSuccessExport()
    {
        $logger = $this->createLoggerInterfaceMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $message = new NullMessage();
        $message->setMessageId(123);
        $message->setBody(json_encode([
            'jobName' => 'name',
            'processorAlias' => 'alias',
        ]));

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(123, Topics::EXPORT .'_alias')
            ->willReturn(true)
        ;

        $processor = new ExportMessageProcessor(
            $this->createExportHandlerMock(),
            $jobRunner,
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::EXPORT],
            ExportMessageProcessor::getSubscribedTopics()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportHandler
     */
    private function createExportHandlerMock()
    {
        return $this->getMock(ExportHandler::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMock(JobRunner::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerInterfaceMock()
    {
        return $this->getMock(LoggerInterface::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }
}
