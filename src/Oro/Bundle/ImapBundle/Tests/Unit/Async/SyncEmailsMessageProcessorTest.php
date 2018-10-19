<?php
namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Bundle\ImapBundle\Async\SyncEmailsMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SyncEmailsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new SyncEmailsMessageProcessor(
            $this->createMessageProducerMock(),
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

        $message = new NullMessage();
        $message->setBody(json_encode(['key' => 'value']));

        $processor = new SyncEmailsMessageProcessor($this->createMessageProducerMock(), $logger);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldSendMessagesToSyncEmailTopic()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->at(0))
            ->method('send')
            ->with($this->equalTo(Topics::SYNC_EMAIL), $this->identicalTo(['id' => 1]))
        ;
        $producer
            ->expects($this->at(1))
            ->method('send')
            ->with($this->equalTo(Topics::SYNC_EMAIL), $this->identicalTo(['id' => 2]))
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['ids' => [1,2]]));

        $processor = new SyncEmailsMessageProcessor($producer, $logger);

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals(
            [Topics::SYNC_EMAILS],
            SyncEmailsMessageProcessor::getSubscribedTopics()
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
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
