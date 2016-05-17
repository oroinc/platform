<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpMessageProducer;
use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\ZeroConfig\Amqp\AmqpQueueProducer;

class AmqpQueueProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpQueueProducer($this->createAmqpSessionMock(), '');
    }

    public function testThrowExceptionIfProcessorNameParameterIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Got message without "processorName" parameter');

        $producer = new AmqpQueueProducer($this->createAmqpSessionMock(), '');
        $producer->send(new AmqpMessage());
    }

    public function testThrowExceptionIfQueueNameParameterIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Got message without "queueName" parameter');

        $message = new AmqpMessage();
        $message->setProperties([
            'processorName' => 'processor-name',
        ]);

        $producer = new AmqpQueueProducer($this->createAmqpSessionMock(), '');
        $producer->send($message);
    }

    public function testShouldSendMessageAndCreateSchema()
    {
        $topic = new AmqpTopic('topic');
        $queue = new AmqpQueue('queue');

        $message = new AmqpMessage();
        $message->setBody('body');
        $message->setProperties([
            'processorName' => 'processor-name',
            'queueName' => 'queue-name',
        ]);

        $messageProducer = $this->createAmqpMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($message))
        ;

        $session = $this->createAmqpSessionMock();
        $session
            ->expects($this->once())
            ->method('createTopic')
            ->with('topic')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue-name')
            ->will($this->returnValue($queue))
        ;
        $session
            ->expects($this->once())
            ->method('createProducer')
            ->with($this->identicalTo($topic))
            ->will($this->returnValue($messageProducer))
        ;
        $session
            ->expects($this->once())
            ->method('declareTopic')
            ->with($this->identicalTo($topic))
        ;
        $session
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($queue))
        ;
        $session
            ->expects($this->once())
            ->method('declareBind')
            ->with($this->identicalTo($topic), $this->identicalTo($queue))
        ;

        $producer = new AmqpQueueProducer($session, 'topic');
        $producer->send($message);
    }

    public function testShouldCreateTopicWithExpectedParameters()
    {
        $topic = new AmqpTopic('topic');
        $queue = new AmqpQueue('queue');

        $message = new AmqpMessage();
        $message->setProperties([
            'processorName' => 'processor-name',
            'queueName' => 'queue-name',
        ]);

        $messageProducer = $this->createAmqpMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($topic), $this->identicalTo($message))
        ;

        $session = $this->createAmqpSessionMock();
        $session
            ->expects($this->once())
            ->method('createTopic')
            ->with('topic')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue-name')
            ->will($this->returnValue($queue))
        ;
        $session
            ->expects($this->once())
            ->method('createProducer')
            ->with($this->identicalTo($topic))
            ->will($this->returnValue($messageProducer))
        ;
        $session
            ->expects($this->once())
            ->method('declareTopic')
            ->willReturnCallback(function (AmqpTopic $topic) {
                $this->assertEquals('queue-name', $topic->getRoutingKey());
                $this->assertEquals('topic', $topic->getTopicName());
                $this->assertEquals('direct', $topic->getType());
                $this->assertFalse($topic->isImmediate());
                $this->assertFalse($topic->isMandatory());
                $this->assertFalse($topic->isPassive());
                $this->assertTrue($topic->isDurable());
                $this->assertFalse($topic->isNoWait());
            })
        ;
        $session
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($queue))
            ->willReturnCallback(function (AmqpQueue $queue) {
                $this->assertEmpty($queue->getConsumerTag());
                $this->assertEquals('queue', $queue->getQueueName());
                $this->assertTrue($queue->isDurable());
                $this->assertFalse($queue->isNoWait());
                $this->assertFalse($queue->isPassive());
                $this->assertFalse($queue->isAutoDelete());
                $this->assertFalse($queue->isExclusive());
                $this->assertFalse($queue->isNoWait());
                $this->assertFalse($queue->isNoAck());
                $this->assertFalse($queue->isNoLocal());
            })
        ;


        $producer = new AmqpQueueProducer($session, 'topic', 'queue');
        $producer->send($message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpQueue
     */
    protected function createAmqpQueueMock()
    {
        return $this->getMock(AmqpQueue::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpTopic
     */
    protected function createAmqpTopicMock()
    {
        return $this->getMock(AmqpTopic::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpSession
     */
    protected function createAmqpSessionMock()
    {
        return $this->getMock(AmqpSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpMessageProducer
     */
    protected function createAmqpMessageProducer()
    {
        return $this->getMock(AmqpMessageProducer::class, [], [], '', false);
    }
}
