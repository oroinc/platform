<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Job;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Job\CalculateRootJobProgressProcessor;
use Oro\Component\MessageQueue\Job\RootJobProgressCalculator;
use Oro\Component\MessageQueue\Job\Topics;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobStorage;

class CalculateRootJobProgressProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCanCreateInstanceViaConstructor()
    {
        $calculateRootJobProgressProcessor = new CalculateRootJobProgressProcessor(
            $this->createJobStorageMock(),
            $this->createRootJobProgressCalculatorMock(),
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );

        $this->assertInstanceOf(MessageProcessorInterface::class, $calculateRootJobProgressProcessor);
        $this->assertInstanceOf(TopicSubscriberInterface::class, $calculateRootJobProgressProcessor);
    }

    public function testCheckingSubscribedTopicNames()
    {
        $this->assertEquals(
            [Topics::CALCULATE_ROOT_JOB_PROGRESS],
            CalculateRootJobProgressProcessor::getSubscribedTopics()
        );
    }

    public function testShouldReturnRejectMessageAndLogErrorIfJobIdNotFoundInMessageBody()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. body: ""')
        ;

        $message = $this->createMessageMock();
        $message
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn('')
        ;

        $processor = new CalculateRootJobProgressProcessor(
            $this->createJobStorageMock(),
            $this->createRootJobProgressCalculatorMock(),
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldReturnRejectMessageAndLogErrorIfJobFound()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Job was not found. id: "11111"')
        ;

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['jobId' => 11111]))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->with('11111')
        ;

        $processor = new CalculateRootJobProgressProcessor(
            $jobStorage,
            $this->createRootJobProgressCalculatorMock(),
            $this->createMessageProducerMock(),
            $logger
        );

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }


    public function testShouldReturnACKMessage()
    {
        $job = new Job();
        $job->setId(11111);

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $message = $this->createMessageMock();
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(json_encode(['jobId' => 11111]))
        ;

        $jobStorage = $this->createJobStorageMock();
        $jobStorage
            ->expects($this->once())
            ->method('findJobById')
            ->willReturn($job)
            ->with('11111')
        ;

        $rootJobProgressCalculator = $this->createRootJobProgressCalculatorMock();
        $rootJobProgressCalculator
            ->expects($this->once())
            ->method('calculate')
            ->with($this->identicalTo($job))
        ;

        $processor = new CalculateRootJobProgressProcessor(
            $jobStorage,
            $rootJobProgressCalculator,
            $this->createMessageProducerMock(),
            $logger
        );
        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageInterface
     */
    private function createMessageMock()
    {
        return $this->createMock(MessageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RootJobProgressCalculator
     */
    private function createRootJobProgressCalculatorMock()
    {
        return $this->createMock(RootJobProgressCalculator::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
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
}
