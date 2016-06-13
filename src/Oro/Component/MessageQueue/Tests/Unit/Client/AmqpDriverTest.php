<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface as TransportMessageProducer;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\AmqpDriver;
use Oro\Component\MessageQueue\Client\Config;

class AmqpDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpDriver($this->createSessionMock(), new Config('', '', '', '', ''));
    }

    public function testShouldCreateMessageInstance()
    {
        $message = new AmqpMessage();

        $expectedProperties = [
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
        ];

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], $expectedProperties)
            ->will($this->returnValue($message))
        ;

        $driver = new AmqpDriver($session, new Config('', '', '', '', ''));
        $result = $driver->createMessage();

        $this->assertSame($message, $result);
    }

    public function testThrowIfGivenPriorityNotSupported()
    {
        $message = new AmqpMessage();

        $session = $this->createSessionMock();

        $driver = new AmqpDriver($session, new Config('', '', '', '', ''));

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Given priority could not be converted to transport\'s one. Got: notSupportedPriority'
        );
        $driver->setMessagePriority($message, $priority = 'notSupportedPriority');
    }

    /**
     * @dataProvider providePriorities
     */
    public function testCorrectlyConvertClientsPriorityToTransportsPriority($clientPriority, $transportPriority)
    {
        $message = new AmqpMessage();

        $session = $this->createSessionMock();

        $driver = new AmqpDriver($session, new Config('', '', '', '', ''));

        $driver->setMessagePriority($message, $clientPriority);

        $this->assertSame($transportPriority, $message->getHeader('priority'));
    }

    public function testShouldCreateProducerInstance()
    {
        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue($this->getMock(TransportMessageProducer::class)))
        ;

        $driver = new AmqpDriver($session, new Config('', '', '', '', ''));
        $result = $driver->createProducer();

        $this->assertInstanceOf(MessageProducer::class, $result);
    }

    public function testShouldReturnConfigInstance()
    {
        $config = new Config('', '', '', '', '');

        $driver = new AmqpDriver($this->createSessionMock(), $config);
        $result = $driver->getConfig();

        $this->assertSame($config, $result);
    }

    public function testShouldCreateQueueWithExpectedParameters()
    {
        $queue = new AmqpQueue('');

        $config = new Config('', '', '', '', '');

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue-name')
            ->will($this->returnValue($queue))
        ;
        $session
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($queue))
        ;

        $driver = new AmqpDriver($session, $config);
        $result = $driver->createQueue('queue-name');

        $this->assertSame($queue, $result);

        $this->assertEmpty($queue->getConsumerTag());
        $this->assertFalse($queue->isExclusive());
        $this->assertFalse($queue->isAutoDelete());
        $this->assertFalse($queue->isPassive());
        $this->assertFalse($queue->isNoWait());
        $this->assertTrue($queue->isDurable());
        $this->assertFalse($queue->isNoAck());
        $this->assertFalse($queue->isNoLocal());
        $this->assertEquals(['x-max-priority' => 5], $queue->getTable());
    }

    public function providePriorities()
    {
        return [
            [MessagePriority::VERY_LOW, 0],
            [MessagePriority::LOW, 1],
            [MessagePriority::NORMAL, 2],
            [MessagePriority::HIGH, 3],
            [MessagePriority::VERY_HIGH, 4],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpSession
     */
    protected function createSessionMock()
    {
        return $this->getMock(AmqpSession::class, [], [], '', false);
    }
}
