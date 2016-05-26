<?php
namespace Oro\Component\MessageQueue\Tests\Unit\ZeroConfig;

use Oro\Component\MessageQueue\Transport\MessageProducer;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\ZeroConfig\FrontProducer;
use Oro\Component\MessageQueue\ZeroConfig\Config;
use Oro\Component\MessageQueue\ZeroConfig\Session;

class FrontProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new FrontProducer($this->createSessionStub());
    }

    public function testShouldSendMessageAndCreateSchema()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($message))
        ;

        $session = $this->createSessionStub($message, $config, $queue, $messageProducer);

        $producer = new FrontProducer($session);
        $producer->send('topic', 'message');

        $expectedProperties = [
            'oro.message_queue.zero_config.topic_name' => 'topic',
            'oro.message_queue.zero_config.processor_name' => 'route-message-processor',
            'oro.message_queue.zero_config.queue_name' => 'router-queue',
        ];

        $this->assertEquals($expectedProperties, $message->getProperties());
    }

    public function testShouldSendNullAsPlainText()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
        ;

        $session = $this->createSessionStub($message, $config, $queue, $messageProducer);

        $producer = new FrontProducer($session);
        $producer->send('topic', null);

        $this->assertSame('', $message->getBody());
        $this->assertSame('text/plain', $message->getHeader('content_type'));
    }

    public function testShouldSendStringAsPlainText()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
        ;

        $session = $this->createSessionStub($message, $config, $queue, $messageProducer);

        $producer = new FrontProducer($session);
        $producer->send('topic', 'message');

        $this->assertSame('message', $message->getBody());
        $this->assertSame('text/plain', $message->getHeader('content_type'));
    }

    public function testShouldSendArrayAsJson()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
        ;

        $session = $this->createSessionStub($message, $config, $queue, $messageProducer);

        $producer = new FrontProducer($session);
        $producer->send('topic', ['foo' => 'fooVal']);

        $this->assertSame('{"foo":"fooVal"}', $message->getBody());
        $this->assertSame('application/json', $message->getHeader('content_type'));
    }

    public function testThrowIfBodyIsNotSerializable()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createMessageProducer();
        $messageProducer
            ->expects($this->never())
            ->method('send')
        ;

        $session = $this->createSessionStub($message, $config, $queue, $messageProducer);

        $producer = new FrontProducer($session);

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'The message\'s body must be either null, scalar or array. Got: stdClass'
        );
        $producer->send('topic', new \stdClass());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createSessionStub($message = null, $config = null, $queue = null, $messageProducer = null)
    {
        $sessionMock = $this->getMock(Session::class, [], [], '', false);
        $sessionMock
            ->expects($this->any())
            ->method('createMessage')
            ->will($this->returnValue($message))
        ;
        $sessionMock
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config))
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createQueue')
            ->will($this->returnValue($queue))
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createProducer')
            ->will($this->returnValue($messageProducer))
        ;

        return $sessionMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducer
     */
    protected function createMessageProducer()
    {
        return $this->getMock(MessageProducer::class, [], [], '', false);
    }
}
