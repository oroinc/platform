<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailsMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SyncEmailsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SyncEmailsMessageProcessor(
            $this->createMock(MessageProducerInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfInvalidMessage()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $message = new Message();
        $message->setBody(json_encode(['key' => 'value']));

        $processor = new SyncEmailsMessageProcessor($this->createMock(MessageProducerInterface::class), $logger);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendMessagesToSyncEmailTopic()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('critical');

        $producer = $this->createMock(MessageProducerInterface::class);
        $producer->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [Topics::SYNC_EMAIL, $this->identicalTo(['id' => 1])],
                [Topics::SYNC_EMAIL, $this->identicalTo(['id' => 2])]
            );

        $message = new Message();
        $message->setBody(json_encode(['ids' => [1,2]]));

        $processor = new SyncEmailsMessageProcessor($producer, $logger);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::SYNC_EMAILS],
            SyncEmailsMessageProcessor::getSubscribedTopics()
        );
    }
}
