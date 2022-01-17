<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topic\SyncEmailTopic;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class SyncEmailMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $this->expectNotToPerformAssertions();

        new SyncEmailMessageProcessor(
            $this->createMock(ImapEmailSynchronizer::class)
        );
    }

    public function testShouldExecuteSyncOriginsWithIds()
    {
        $synchronizer = $this->createMock(ImapEmailSynchronizer::class);
        $synchronizer->expects($this->once())
            ->method('syncOrigins')
            ->with($this->identicalTo([1]));

        $message = new Message();
        $message->setBody(['id' => 1]);

        $processor = new SyncEmailMessageProcessor($synchronizer);

        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [SyncEmailTopic::getName()],
            SyncEmailMessageProcessor::getSubscribedTopics()
        );
    }
}
