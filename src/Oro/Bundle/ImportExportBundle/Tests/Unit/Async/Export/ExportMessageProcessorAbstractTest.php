<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Async\Export;

use Oro\Bundle\ImportExportBundle\Async\Export\ExportMessageProcessorAbstract;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ExportMessageProcessorAbstractTest extends \PHPUnit\Framework\TestCase
{
    public function testMustImplementMessageProcessorAndTopicSubscriberInterfaces()
    {
        $processor = $this->createMock(ExportMessageProcessorAbstract::class);

        $this->assertInstanceOf(MessageProcessorInterface::class, $processor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $processor);
    }

    public function testCanBeConstructedWithRequiredAttributes()
    {
        $processor = $this->getMockBuilder(ExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $this->createJobRunnerMock(),
                $this->createJobStorageMock(),
                $this->createLoggerMock(),
            ])
            ->setMethods(['getSubscribedTopics', 'handleExport', 'getMessageBody'])
            ->getMock()
        ;

        $this->assertInstanceOf(ExportMessageProcessorAbstract::class, $processor);
    }

    public function testShouldRejectMessageIfGetMessageBodyReturnFalse()
    {
        $processor = $this->getMockBuilder(ExportMessageProcessorAbstract::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSubscribedTopics', 'handleExport', 'getMessageBody'])
            ->getMock()
        ;

        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(false)
        ;

        $message = new NullMessage();

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessorAbstract::REJECT, $result);
    }

    public function runDelayedJobResultProvider()
    {
        return [
            [ true, ExportMessageProcessorAbstract::ACK ],
            [ false, ExportMessageProcessorAbstract::REJECT ],
        ];
    }

    /**
     * @dataProvider runDelayedJobResultProvider
     * @param string $jobResult
     * @param string $expectedResult
     */
    public function testShouldReturnMessageStatusDependsOfJobResult($jobResult, $expectedResult)
    {
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runDelayed')
            ->with($this->equalTo(1))
            ->willReturn($jobResult)
        ;

        $processor = $this->getMockBuilder(ExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $jobRunner,
                $this->createJobStorageMock(),
                $this->createLoggerMock(),
            ])
            ->setMethods(['getSubscribedTopics', 'handleExport', 'getMessageBody'])
            ->getMock()
        ;
        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => 1])
        ;

        $message = new NullMessage();

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals($expectedResult, $result);
    }

    public function testShouldHandleExportLogMessageAndSaveJobResult()
    {
        $exportResult = ['success' => true, 'readsCount' => 10, 'errorsCount' => 0];

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

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Export result. Success: Yes. ReadsCount: 10. ErrorsCount: 0'))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('saveJob')
        ;

        $processor = $this->getMockBuilder(ExportMessageProcessorAbstract::class)
            ->setConstructorArgs([
                $jobRunner,
                $jobStorage,
                $logger,
            ])
            ->setMethods(['getSubscribedTopics', 'handleExport', 'getMessageBody'])
            ->getMock()
        ;
        $processor
            ->expects($this->once())
            ->method('getMessageBody')
            ->willReturn(['jobId' => 1])
        ;
        $processor
            ->expects($this->once())
            ->method('handleExport')
            ->with($this->equalTo(['jobId' => 1]))
            ->willReturn($exportResult)
        ;

        $message = new NullMessage();

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(ExportMessageProcessorAbstract::ACK, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
