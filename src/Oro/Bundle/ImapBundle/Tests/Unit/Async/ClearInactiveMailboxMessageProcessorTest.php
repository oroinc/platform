<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\ClearInactiveMailboxMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topic\ClearInactiveMailboxTopic;
use Oro\Bundle\ImapBundle\Manager\ImapClearManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class ClearInactiveMailboxMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        $this->expectNotToPerformAssertions();

        new ClearInactiveMailboxMessageProcessor(
            $this->createMock(ImapClearManager::class),
            $this->createMock(JobRunner::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $expectedSubscribedTopics = [
            ClearInactiveMailboxTopic::getName()
        ];

        $this->assertEquals($expectedSubscribedTopics, ClearInactiveMailboxMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRunJob()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $clearManager = $this->createMock(ImapClearManager::class);
        $clearManager->expects($this->once())
            ->method('setLogger')
            ->with($logger);

        $message = new Message();
        $message->setMessageId('12345');
        $message->setBody([]);

        $jobRunner = $this->createMock(JobRunner::class);
        $jobRunner->expects($this->once())
            ->method('runUniqueByMessage')
            ->with($message);

        $processor = new ClearInactiveMailboxMessageProcessor($clearManager, $jobRunner, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }
}
