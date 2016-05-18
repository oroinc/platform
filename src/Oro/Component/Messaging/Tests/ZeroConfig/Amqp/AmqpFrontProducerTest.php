<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpMessageProducer;
use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\ZeroConfig\Amqp\AmqpFrontProducer;
use Oro\Component\Messaging\ZeroConfig\Config;

class AmqpFrontProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpFrontProducer($this->createAmqpSessionMock(), new Config('', '', '', '', ''));
    }

    public function testThrowExceptionIfMessageNameParameterIsNotSet()
    {
        $this->setExpectedException(\LogicException::class, 'Got message without "messageName" parameter');

        $producer = new AmqpFrontProducer($this->createAmqpSessionMock(), new Config('', '', '', '', ''));
        $producer->send(new AmqpMessage());
    }

    public function testShouldSendMessageAndCreateSchema()
    {
        $topic = new AmqpTopic('topic');
        $queue = new AmqpQueue('queue');

        $message = new AmqpMessage();
        $message->setBody('body');
        $message->setProperties([
            'messageName' => 'name',
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
            ->with('queue')
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

        $producer = new AmqpFrontProducer($session, new Config('', 'topic', 'queue', '', ''));
        $producer->send($message);
    }

    public function testShouldCreateTopicWithExpectedParameters()
    {
        $topic = new AmqpTopic('topic');
        $queue = new AmqpQueue('queue');

        $message = new AmqpMessage();
        $message->setProperties([
            'messageName' => 'name',
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
            ->with('queue')
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
                $this->assertEmpty($topic->getRoutingKey());
                $this->assertEquals('topic', $topic->getTopicName());
                $this->assertEquals('fanout', $topic->getType());
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


        $producer = new AmqpFrontProducer($session, new Config('', 'topic', 'queue', '', ''));
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
