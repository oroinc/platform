<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\AutoResponsesMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class AutoResponsesMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AutoResponsesMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfBodyIsInvalid()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $processor = new AutoResponsesMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(JobRunner::class),
            $logger
        );

        $message = new Message();
        $message->setBody(json_encode(['key' => 'value'], JSON_THROW_ON_ERROR));

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldPublishMessageToProducer()
    {
        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects($this->once())
            ->method('send')
            ->with('oro.email.send_auto_response', ['id' => 1, 'jobId' => 12345]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('error');

        $message = new Message();
        $message->setBody(json_encode(['ids' => [1]], JSON_THROW_ON_ERROR));
        $message->setMessageId('message-id');

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUnique')
            ->with('message-id', 'oro.email.send_auto_responses' . ':' . md5('1'))
            ->willReturnCallback(function ($ownerId, $name, $callback) use ($jobRunner) {
                $callback($jobRunner);

                return true;
            });

        $jobRunner->expects($this->once())
            ->method('createDelayed')
            ->with('oro.email.send_auto_response' . ':1')
            ->willReturnCallback(function ($name, $callback) use ($jobRunner) {
                $job = new Job();
                $job->setId(12345);

                $callback($jobRunner, $job);
            });

        $processor = new AutoResponsesMessageProcessor($producer, $jobRunner, $logger);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::SEND_AUTO_RESPONSES], AutoResponsesMessageProcessor::getSubscribedTopics());
    }
}
