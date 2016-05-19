<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\ZeroConfig\FrontProducer;
use Oro\Component\Messaging\ZeroConfig\QueueProducer;
use Oro\Component\Messaging\ZeroConfig\AmqpSession;
use Oro\Component\Messaging\ZeroConfig\Config;

class AmqpSessionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpSession($this->createTransportSessionMock(), new Config('', '', '', '', ''));
    }

    public function testShouldCreateMessageInstance()
    {
        $message = new AmqpMessage();

        $expectedProperties = [
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
        ];

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], $expectedProperties)
            ->will($this->returnValue($message))
        ;

        $session = new AmqpSession($transportSession, new Config('', '', '', '', ''));
        $result = $session->createMessage();

        $this->assertSame($message, $result);
    }

    public function testShouldCreateFrontProducerInstance()
    {
        $session = new AmqpSession($this->createTransportSessionMock(), new Config('', '', '', '', ''));
        $result = $session->createFrontProducer();

        $this->assertInstanceOf(FrontProducer::class, $result);
    }

    public function testShouldCreateQueueProducerInstance()
    {
        $session = new AmqpSession($this->createTransportSessionMock(), new Config('', '', '', '', ''));
        $result = $session->createQueueProducer();

        $this->assertInstanceOf(QueueProducer::class, $result);
    }

    public function testShouldCreateRouterTopic()
    {
        $topic = new AmqpTopic('');

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createTopic')
            ->with('router-topic')
            ->will($this->returnValue($topic))
        ;

        $session = new AmqpSession($transportSession, new Config('', 'router-topic', '', '', ''));
        $resultTopic = $session->createRouterTopic();

        $this->assertSame($topic, $resultTopic);

        $this->assertEmpty($resultTopic->getRoutingKey());
        $this->assertEquals('fanout', $resultTopic->getType());
        $this->assertFalse($resultTopic->isImmediate());
        $this->assertFalse($resultTopic->isMandatory());
        $this->assertFalse($resultTopic->isPassive());
        $this->assertTrue($resultTopic->isDurable());
        $this->assertFalse($resultTopic->isNoWait());
    }

    public function testShouldCreateRouterQueue()
    {
        $queue = new AmqpQueue('');

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createQueue')
            ->with('router-queue')
            ->will($this->returnValue($queue))
        ;

        $session = new AmqpSession($transportSession, new Config('', '', 'router-queue', '', ''));
        $resultQueue = $session->createRouterQueue();

        $this->assertSame($queue, $resultQueue);

        $this->assertEmpty($resultQueue->getConsumerTag());
        $this->assertTrue($resultQueue->isDurable());
        $this->assertFalse($resultQueue->isNoWait());
        $this->assertFalse($resultQueue->isPassive());
        $this->assertFalse($resultQueue->isAutoDelete());
        $this->assertFalse($resultQueue->isExclusive());
        $this->assertFalse($resultQueue->isNoWait());
        $this->assertFalse($resultQueue->isNoAck());
        $this->assertFalse($resultQueue->isNoLocal());
    }

    public function testShouldCreateQueueTopic()
    {
        $topic = new AmqpTopic('');

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createTopic')
            ->with('queue-topic')
            ->will($this->returnValue($topic))
        ;

        $session = new AmqpSession($transportSession, new Config('', '', '', 'queue-topic', ''));
        $resultTopic = $session->createQueueTopic('name');

        $this->assertSame($topic, $resultTopic);

        $this->assertEquals('name', $topic->getRoutingKey());
        $this->assertEquals('direct', $resultTopic->getType());
        $this->assertFalse($resultTopic->isImmediate());
        $this->assertFalse($resultTopic->isMandatory());
        $this->assertFalse($resultTopic->isPassive());
        $this->assertTrue($resultTopic->isDurable());
        $this->assertFalse($resultTopic->isNoWait());
    }

    public function testShouldCreateQueueQueue()
    {
        $queue = new AmqpQueue('');

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue-queue')
            ->will($this->returnValue($queue))
        ;

        $session = new AmqpSession($transportSession, new Config('', '', '', '', ''));
        $resultQueue = $session->createQueueQueue('queue-queue');

        $this->assertSame($queue, $resultQueue);

        $this->assertEmpty($resultQueue->getConsumerTag());
        $this->assertTrue($resultQueue->isDurable());
        $this->assertFalse($resultQueue->isNoWait());
        $this->assertFalse($resultQueue->isPassive());
        $this->assertFalse($resultQueue->isAutoDelete());
        $this->assertFalse($resultQueue->isExclusive());
        $this->assertFalse($resultQueue->isNoWait());
        $this->assertFalse($resultQueue->isNoAck());
        $this->assertFalse($resultQueue->isNoLocal());
    }

    public function testShouldReturnTransportSession()
    {
        $transportSession = $this->createTransportSessionMock();
        $session = new AmqpSession($transportSession, new Config('', '', '', '', ''));

        $this->assertSame($transportSession, $session->getTransportSession());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportAmqpSession
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(TransportAmqpSession::class, [], [], '', false);
    }
}
