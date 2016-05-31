<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\Extension\DelayRedeliveredMessageAmqpExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessageProducer;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\NullLogger;

class DelayRedeliveredMessageAmqpExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, DelayRedeliveredMessageAmqpExtension::class);
    }
    
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new DelayRedeliveredMessageAmqpExtension();
    }
    
    public function testShouldDoNothingIfNotAmqpSession()
    {
        /** @var SessionInterface $session */
        $session = $this->getMock(SessionInterface::class);
        
        $context = new Context(
            $session,
            $this->createMessageConsumerStub(),
            $this->createMessageProcessorMock(),
            new NullLogger()
        );

        $extension = new DelayRedeliveredMessageAmqpExtension();

        $extension->onPreReceived($context);
    }

    public function testShouldDoNothingIfNotRedeliveredMessage()
    {
        $message = new AmqpMessage();
        $message->setRedelivered(false);
        
        $context = new Context(
            $this->createAmqpSessionStub(),
            $this->createMessageConsumerStub(),
            $this->createMessageProcessorMock(),
            new NullLogger()
        );
        $context->setMessage($message);

        $extension = new DelayRedeliveredMessageAmqpExtension();

        $extension->onPreReceived($context);
    }

    public function testShouldRejectRedeliveredMessageAndPublishItToDelayedQueue()
    {
        $deadMessage = new AmqpMessage();

        $message = new AmqpMessage();
        $message->setRedelivered(true);

        $messageProducerMock = $this->createAmqpMessageProducer();
        $messageProducerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(AmqpQueue::class), $this->identicalTo($deadMessage))
        ;

        $context = new Context(
            $this->createAmqpSessionStub($deadMessage, $messageProducerMock),
            $this->createMessageConsumerStub('aQueueName'),
            $this->createMessageProcessorMock(),
            new NullLogger()
        );
        $context->setMessage($message);

        $extension = new DelayRedeliveredMessageAmqpExtension();

        $extension->onPreReceived($context);

        $this->assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
    }

    public function testShouldDeclareDelayedQueueBeforeUsingIt()
    {
        $deadMessage = new AmqpMessage();

        $message = new AmqpMessage();
        $message->setRedelivered(true);

        $sessionMock = $this->createAmqpSessionStub($deadMessage, $this->createAmqpMessageProducer());
        $sessionMock
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->isInstanceOf(AmqpQueue::class))
            ->willReturnCallback(function (AmqpQueue $queue) {
                $this->assertEquals('theQueueName.delayed', $queue->getQueueName());
                $this->assertTrue($queue->isDurable());
                $this->assertFalse($queue->isAutoDelete());
                $this->assertFalse($queue->isExclusive());
                $this->assertFalse($queue->isPassive());
                $this->assertFalse($queue->isNoAck());
                $this->assertFalse($queue->isNoLocal());
                $this->assertFalse($queue->isNoWait());
                $this->assertEquals(
                    [
                        'x-dead-letter-exchange' => '',
                        'x-dead-letter-routing-key' => 'theQueueName',
                        'x-message-ttl' => 5000,
                        'x-expires' => 200000,
                    ],
                    $queue->getTable()
                );
            })
        ;

        $context = new Context(
            $sessionMock,
            $this->createMessageConsumerStub('theQueueName'),
            $this->createMessageProcessorMock(),
            new NullLogger()
        );
        $context->setMessage($message);

        $extension = new DelayRedeliveredMessageAmqpExtension();

        $extension->onPreReceived($context);
    }

    public function testShouldTakeEverythingFromRedeliveredMessageAndCreateDelayedOne()
    {
        $deadMessage = new AmqpMessage();

        $message = new AmqpMessage();
        $message->setBody('theMessageBody');
        $message->setProperties(['aProp' => 'aPropVal']);
        $message->setHeaders(['aHeader' => 'aHeaderVal']);
        $message->setRedelivered(true);

        $sessionMock = $this->createAmqpSessionStub($deadMessage, $this->createAmqpMessageProducer());
        $sessionMock
            ->expects($this->once())
            ->method('createMessage')
            ->with('theMessageBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal'])
            ->willReturn($deadMessage)
        ;

        $context = new Context(
            $sessionMock,
            $this->createMessageConsumerStub('aQueueName'),
            $this->createMessageProcessorMock(),
            new NullLogger()
        );
        $context->setMessage($message);

        $extension = new DelayRedeliveredMessageAmqpExtension();

        $extension->onPreReceived($context);
    }

    public function testShouldIgnoreXDeathPropertyDueToBugInRabbitMQ()
    {
        $deadMessage = new AmqpMessage();

        $message = new AmqpMessage();
        $message->setProperties(['x-death' => 'x-deathVal', 'aProp' => 'aPropVal']);
        $message->setRedelivered(true);

        $sessionMock = $this->createAmqpSessionStub($deadMessage, $this->createAmqpMessageProducer());
        $sessionMock
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->anything(), ['aProp' => 'aPropVal'], $this->anything())
            ->willReturn($deadMessage)
        ;

        $context = new Context(
            $sessionMock,
            $this->createMessageConsumerStub('aQueueName'),
            $this->createMessageProcessorMock(),
            new NullLogger()
        );
        $context->setMessage($message);

        $extension = new DelayRedeliveredMessageAmqpExtension();

        $extension->onPreReceived($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpSession
     */
    protected function createAmqpSessionStub($deadMessage = null, $messageProducer = null)
    {
        $sessionMock = $this->getMock(AmqpSession::class, [], [], '', false);
        $sessionMock
            ->expects($this->any())
            ->method('createMessage')
            ->willReturn($deadMessage)
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturnCallback(function ($name) {
                return new AmqpQueue($name);
            })
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createProducer')
            ->willReturn($messageProducer)
        ;

        return $sessionMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageConsumerInterface
     */
    protected function createMessageConsumerStub($queueName = null)
    {
        $queue = new AmqpQueue($queueName);

        $messageConsumerMock = $this->getMock(MessageConsumerInterface::class);
        $messageConsumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        return $messageConsumerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpMessageProducer
     */
    protected function createAmqpMessageProducer()
    {
        return $this->getMock(AmqpMessageProducer::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessorMock()
    {
        return $this->getMock(MessageProcessorInterface::class);
    }
}
