<?php
namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ImapBundle\Async\SyncEmailMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Bundle\ImapBundle\Sync\ImapEmailSynchronizer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class SyncEmailMessageProcessorTest extends \PHPUnit_Framework_TestCase
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
            ->with('Got invalid message. "{"key":"value"}"')
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
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ImapEmailSynchronizer
     */
    private function createImapEmailSynchronizerMock()
    {
        return $this->getMock(ImapEmailSynchronizer::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
