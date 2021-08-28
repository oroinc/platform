<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SyncEmailMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SyncEmailMessageProcessor(
            $this->createMock(ImapEmailSynchronizer::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldRejectMessageIfInvalidMessage()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('critical')
            ->with('Got invalid message');

        $synchronizer = $this->createMock(ImapEmailSynchronizer::class);

        $message = new Message();
        $message->setBody(json_encode(['key' => 'value'], JSON_THROW_ON_ERROR));

        $processor = new SyncEmailMessageProcessor($synchronizer, $logger);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldExecuteSyncOriginsWithIds()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())
            ->method('critical');

        $synchronizer = $this->createMock(ImapEmailSynchronizer::class);
        $synchronizer->expects($this->once())
            ->method('syncOrigins')
            ->with($this->identicalTo([1]));

        $message = new Message();
        $message->setBody(json_encode(['id' => 1], JSON_THROW_ON_ERROR));

        $processor = new SyncEmailMessageProcessor($synchronizer, $logger);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::SYNC_EMAIL],
            SyncEmailMessageProcessor::getSubscribedTopics()
        );
    }
}
