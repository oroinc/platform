<?php
namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SyncEmailMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SyncEmailMessageProcessor(
            $this->createImapEmailSynchronizerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfInvalidMessage()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message')
        ;

        $synchronizer = $this->createImapEmailSynchronizerMock();

        $message = new NullMessage();
        $message->setBody(json_encode(['key' => 'value']));

        $processor = new SyncEmailMessageProcessor($synchronizer, $logger);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldExecuteSyncOriginsWithIds()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $synchronizer = $this->createImapEmailSynchronizerMock();
        $synchronizer
            ->expects($this->once())
            ->method('syncOrigins')
            ->with($this->identicalTo([1]))
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['id' => 1]));

        $processor = new SyncEmailMessageProcessor($synchronizer, $logger);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::SYNC_EMAIL],
            SyncEmailMessageProcessor::getSubscribedTopics()
        );
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ImapEmailSynchronizer
     */
    private function createImapEmailSynchronizerMock()
    {
        return $this->createMock(ImapEmailSynchronizer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
