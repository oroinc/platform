<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\AutoResponsesMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AutoResponsesMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AutoResponsesMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfBodyIsInvalid()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $processor = new AutoResponsesMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createJobRunnerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode(['key' => 'value']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldPublishMessageToProducer()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with('oro.email.send_auto_response', ['id' => 1, 'jobId' => 12345])
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(
            ['ids' => [1]]
        ));
        $message->setMessageId('message-id');

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with('message-id', 'oro.email.send_auto_responses' . ':'.md5('1'))
            ->will($this->returnCallback(function ($ownerId, $name, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            }))
        ;

        $jobRunner
            ->expects($this->once())
            ->method('createDelayed')
            ->with('oro.email.send_auto_response' . ':1')
            ->will($this->returnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(12345);

                $callback($jobRunner, $job);
            }))
        ;

        $processor = new AutoResponsesMessageProcessor($producer, $jobRunner, $logger);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::SEND_AUTO_RESPONSES], AutoResponsesMessageProcessor::getSubscribedTopics());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }


    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
