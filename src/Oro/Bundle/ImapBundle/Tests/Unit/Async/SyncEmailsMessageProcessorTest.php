<?php
namespace Oro\Bundle\ImapBundle\Tests\Unit\Async;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Psr\Log\LoggerInterface;

use Oro\Bundle\ImapBundle\Async\SyncEmailsMessageProcessor;
use Oro\Bundle\ImapBundle\Async\Topics;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class SyncEmailsMessageProcessorTest extends \PHPUnit_Framework_TestCase
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
            ->with('Got invalid message. "{"key":"value"}"')
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
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->getMock(MessageProducerInterface::class);
    }
}
