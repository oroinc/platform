<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailsMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailsTopic;
use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailTopic;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\TestCase;

class SyncEmailsMessageProcessorTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        $this->expectNotToPerformAssertions();

        new SyncEmailsMessageProcessor(
            $this->createMock(MessageProducerInterface::class)
        );
    }

    public function testShouldSendMessagesToSyncEmailTopic(): void
    {
        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [SyncEmailTopic::getName(), $this->identicalTo(['id' => 1])],
                [SyncEmailTopic::getName(), $this->identicalTo(['id' => 2])]
            );

        $message = new Message();
        $message->setBody(['ids' => [1, 2]]);

        $processor = new SyncEmailsMessageProcessor($producer);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics(): void
    {
        $this->assertEquals(
            [SyncEmailsTopic::getName()],
            SyncEmailsMessageProcessor::getSubscribedTopics()
        );
    }
}
