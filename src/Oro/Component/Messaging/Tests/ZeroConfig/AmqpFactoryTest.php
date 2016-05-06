<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\ZeroConfig\AmqpFactory;
use PhpAmqpLib\Channel\AMQPChannel;

class AmqpFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructed()
    {
        new AmqpFactory($this->createAmqpSessionMock(), 'routerTopicName', 'routerQueueName');
    }

    public function testCreateRouterMessageShouldCreateInstanceOfAmqpMessage()
    {
        $session = new AmqpSession($this->createAmqpChannelMock());
        $factory = new AmqpFactory($session, '', '');

        $message = $factory->createRouterMessage('name', 'body');

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Amqp\AmqpMessage', $message);
    }

    public function testCreateRouterMessageShouldCreateMessageWithMessageNameAsProperty()
    {
        $session = new AmqpSession($this->createAmqpChannelMock());
        $factory = new AmqpFactory($session, '', '');

        $message = $factory->createRouterMessage('name', 'body');

        $expectedProperties = [
            'messageName' => 'name',
        ];

        $this->assertEquals('body', $message->getBody());
        $this->assertEquals($expectedProperties, $message->getProperties());
    }

    public function testCreateRouterTopicShouldCreateInstanceOfAmqpTopic()
    {
        $session = new AmqpSession($this->createAmqpChannelMock());
        $factory = new AmqpFactory($session, '', '');

        $topic = $factory->createRouterTopic();

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Amqp\AmqpTopic', $topic);
    }

    public function testCreateRouterTopicShouldCreateTopicWithExpectedProperites()
    {
        $session = new AmqpSession($this->createAmqpChannelMock());
        $factory = new AmqpFactory($session, 'routerTopicName', '');

        $topic = $factory->createRouterTopic();

        $this->assertEquals('routerTopicName', $topic->getTopicName());
        $this->assertEquals('fanout', $topic->getType());
        $this->assertEmpty($topic->getRoutingKey());
        $this->assertTrue($topic->isDurable());
        $this->assertFalse($topic->isImmediate());
        $this->assertFalse($topic->isMandatory());
        $this->assertFalse($topic->isNoWait());
        $this->assertFalse($topic->isPassive());
    }

    public function testCreateRouterQueueShouldCreateInstanceOfAmqpQueue()
    {
        $session = new AmqpSession($this->createAmqpChannelMock());
        $factory = new AmqpFactory($session, '', '');

        $queue = $factory->createRouterQueue();

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Amqp\AmqpQueue', $queue);
    }

    public function testCreateRouterQueueShouldCreateQueueWithExpectedProperites()
    {
        $session = new AmqpSession($this->createAmqpChannelMock());
        $factory = new AmqpFactory($session, '', 'routerQueueName');

        $queue = $factory->createRouterQueue();

        $this->assertEquals('routerQueueName', $queue->getQueueName());
        $this->assertEmpty($queue->getConsumerTag());
        $this->assertFalse($queue->isNoWait());
        $this->assertFalse($queue->isPassive());
        $this->assertTrue($queue->isDurable());
        $this->assertFalse($queue->isAutoDelete());
        $this->assertFalse($queue->isExclusive());
        $this->assertFalse($queue->isNoAck());
        $this->assertFalse($queue->isNoLocal());
    }

    public function testCreateRouterMessageProducerShouldCreateInstanceOfAmqpMessageProducer()
    {
        $session = new AmqpSession($this->createAmqpChannelMock());
        $factory = new AmqpFactory($session, '', '');

        $producer = $factory->createRouterMessageProducer();

        $this->assertInstanceOf('Oro\Component\Messaging\Transport\Amqp\AmqpMessageProducer', $producer);
    }

    public function testCreateRouterMessageProducerShouldDeclareRequiredTopicQueueBind()
    {
        $topic = new AmqpTopic('name');
        $queue = new AmqpQueue('name');

        $session = $this->createAmqpSessionMock();
        $session
            ->expects($this->exactly(2))
            ->method('createTopic')
            ->will($this->returnValue($topic))
        ;
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->will($this->returnValue($queue))
        ;
        $session
            ->expects($this->once())
            ->method('declareTopic')
            ->with($this->equalTo($topic))
        ;
        $session
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->equalTo($queue))
        ;
        $session
            ->expects($this->once())
            ->method('declareBind')
            ->with($this->equalTo($topic), $this->equalTo($queue))
        ;

        $factory = new AmqpFactory($session, '', '');

        $producer = $factory->createRouterMessageProducer();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpSession
     */
    protected function createAmqpSessionMock()
    {
        return $this->getMock('Oro\Component\Messaging\Transport\Amqp\AmqpSession', [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAmqpChannelMock()
    {
        return $this->getMock('\PhpAmqpLib\Channel\AMQPChannel', [], [], '', false);
    }
}
