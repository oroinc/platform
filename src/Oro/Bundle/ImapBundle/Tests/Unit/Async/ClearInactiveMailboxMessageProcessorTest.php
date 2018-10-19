<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\ClearInactiveMailboxMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Bundle\ImapBundle\Manager\ImapClearManager;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ClearInactiveMailboxMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ClearInactiveMailboxMessageProcessor(
            $this->createImapClearManagerMock(),
            $this->createJobRunnerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldBeSubscribedForTopics()
    {
        $expectedSubscribedTopics = [
            Topics::CLEAR_INACTIVE_MAILBOX
        ];

        $this->assertEquals($expectedSubscribedTopics, ClearInactiveMailboxMessageProcessor::getSubscribedTopics());
    }

    public function testShouldRunJob()
    {
        $logger = $this->createLoggerMock();

        $clearManager = $this->createImapClearManagerMock();
        $clearManager
            ->expects($this->once())
            ->method('setLogger')
            ->with($logger)
        ;

        $message = new NullMessage();
        $message->setMessageId('12345');
        $message->setBody(JSON::encode([]));

        $jobRunner = $this->createJobRunnerMock();
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with('12345', 'oro.imap.clear_inactive_mailbox')
        ;

        $processor = new ClearInactiveMailboxMessageProcessor($clearManager, $jobRunner, $logger);
        $result = $processor->process($message, $this->createMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | ImapClearManager
     */
    private function createImapClearManagerMock()
    {
        return $this->getMockBuilder(ImapClearManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
