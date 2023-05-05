<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Oro\Bundle\EmailBundle\Async\AutoResponsesMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topic\SendAutoResponsesTopic;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class AutoResponsesMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $this->expectNotToPerformAssertions();

        new AutoResponsesMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(JobRunner::class)
        );
    }

    public function testShouldPublishMessageToProducer()
    {
        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects($this->once())
            ->method('send')
            ->with('oro.email.send_auto_response', ['id' => 1, 'jobId' => 12345]);

        $message = new Message();
        $message->setBody(['ids' => [1]]);
        $message->setMessageId('message-id');

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->with($message)
            ->willReturnCallback(function ($message, $callback) use ($jobRunner) {
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

        $processor = new AutoResponsesMessageProcessor($producer, $jobRunner);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([SendAutoResponsesTopic::getName()], AutoResponsesMessageProcessor::getSubscribedTopics());
    }
}
