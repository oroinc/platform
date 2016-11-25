<?php
namespace Oro\Bundle\EmailBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Oro\Bundle\EmailBundle\Async\AutoResponsesMessageProcessor;
use Oro\Bundle\EmailBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class AutoResponsesMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AutoResponsesMessageProcessor(
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldRejectMessageIfBodyIsInvalid()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('[AutoResponsesMessageProcessor] Got invalid message. "{"key":"value"}"')
        ;

        $processor = new AutoResponsesMessageProcessor(
            $this->createMessageProducerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(json_encode(['key' => 'value']));

        $result = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }

    public function testShouldPublishMessageToProducer()
    {
        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::SEND_AUTO_RESPONSE, ['id' => 1])
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $processor = new AutoResponsesMessageProcessor($producer, $logger);

        $message = new NullMessage();
        $message->setBody(json_encode(
            ['ids' => [1]]
        ));

        $result = $processor->process($message, $this->getMock(SessionInterface::class));

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testShouldReturnSubscribedTopics()
    {
        $this->assertEquals([Topics::SEND_AUTO_RESPONSES], AutoResponsesMessageProcessor::getSubscribedTopics());
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
