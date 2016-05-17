<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\ZeroConfig\Amqp\AmqpFrontProducer;
use Oro\Component\Messaging\ZeroConfig\Amqp\AmqpQueueProducer;
use Oro\Component\Messaging\ZeroConfig\Amqp\AmqpSession;

class AmqpSessionTest extends \PHPUnit_Framework_TestCase
{
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

        $session = new AmqpSession($transportSession, '', '', '');
        $result = $session->createMessage();

        $this->assertSame($message, $result);
    }

    public function testShouldCreateFrontProducerInstance()
    {
        $session = new AmqpSession($this->createTransportSessionMock(), '', '', '');
        $result = $session->createFrontProducer();

        $this->assertInstanceOf(AmqpFrontProducer::class, $result);
    }

    public function testShouldCreateQueueProducerInstance()
    {
        $session = new AmqpSession($this->createTransportSessionMock(), '', '', '');
        $result = $session->createQueueProducer('queue-name');

        $this->assertInstanceOf(AmqpQueueProducer::class, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportAmqpSession
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(TransportAmqpSession::class, [], [], '', false);
    }
}
