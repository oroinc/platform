<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\CalculateRootJobStatusCase;
use Oro\Component\MessageQueue\Job\CalculateRootJobStatusProcessor;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;
use Oro\Component\MessageQueue\Job\Topics;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class CalculateRootJobStatusProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new CalculateRootJobStatusProcessor(
            $this->createJobStorageMock(),
            $this->createCalculateRootJobStatusCaseMock(),
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldReturnSubscribedTopicNames()
    {
        $this->assertEquals(
            [Topics::CALCULATE_ROOT_JOB_STATUS],
            CalculateRootJobStatusProcessor::getSubscribedTopics()
        );
    }

    public function testShouldLogErrorAndRejectMessageIfMessageIsInvalid()
    {
        $message = new NullMessage();
        $message->setBody('');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. body: ""')
        ;

        $processor = new CalculateRootJobStatusProcessor(
            $this->createJobStorageMock(),
            $this->createCalculateRootJobStatusCaseMock(),
            $this->createMessageProducerMock(),
            $logger
        );
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogErrorIfJobWasNotFound()
    {
        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with('12345')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Job was not found. id: "12345"')
        ;

        $case = $this->createCalculateRootJobStatusCaseMock();
        $case
            ->expects($this->never())
            ->method('calculate')
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 12345]));

        $processor = new CalculateRootJobStatusProcessor($storage, $case, $producer, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldCallCalculateJobRootStatusAndACKMessage()
    {
        $job = new Job();

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with('12345')
            ->will($this->returnValue($job))
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $case = $this->createCalculateRootJobStatusCaseMock();
        $case
            ->expects($this->once())
            ->method('calculate')
            ->with($this->identicalTo($job))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 12345]));

        $processor = new CalculateRootJobStatusProcessor($storage, $case, $producer, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldSendRootJobStoppedMessageIfJobHasStopped()
    {
        $rootJob = new Job();
        $rootJob->setId(12345);
        $job = new Job();
        $job->setRootJob($rootJob);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with('12345')
            ->will($this->returnValue($job))
        ;

        $logger = $this->createLoggerMock();

        $case = $this->createCalculateRootJobStatusCaseMock();
        $case
            ->expects($this->once())
            ->method('calculate')
            ->with($this->identicalTo($job))
            ->will($this->returnValue(true))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::ROOT_JOB_STOPPED, ['jobId' => 12345])
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 12345]));

        $processor = new CalculateRootJobStatusProcessor($storage, $case, $producer, $logger);
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CalculateRootJobStatusCase
     */
    private function createCalculateRootJobStatusCaseMock()
    {
        return $this->getMock(CalculateRootJobStatusCase::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->getMock(JobStorage::class, [], [], '', false);
    }
}
