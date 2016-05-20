<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Connection;
use Oro\Component\Messaging\Transport\Null\NullConnection;
use Oro\Component\Messaging\Transport\Null\NullSession as TransportNullSession;
use Oro\Component\Messaging\ZeroConfig\AmqpSession;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\NullSession;
use Oro\Component\Messaging\ZeroConfig\SessionFactory;

class SessionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldCreateAmpqSessionInstance()
    {
        $config = new Config('', '', '', '');

        $connection = $this->createAmqpConnectionMock();
        $connection
            ->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($this->createTransportAmqpSessionMock()))
        ;

        $session = SessionFactory::create($connection, $config);

        $this->assertInstanceOf(AmqpSession::class, $session);
    }

    public function testShouldCreateNullSessionInstance()
    {
        $config = new Config('', '', '', '');

        $connection = $this->createNullConnectionMock();
        $connection
            ->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($this->createTransportNullSessionMock()))
        ;

        $session = SessionFactory::create($connection, $config);

        $this->assertInstanceOf(NullSession::class, $session);
    }

    public function testShouldThrowExceptionIfUnexpectedConnectionInstance()
    {
        $this->setExpectedException(\LogicException::class, 'Unexpected connection instance: "Mock_Connection_');

        SessionFactory::create($this->getMock(Connection::class), new Config('', '', '', ''));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportAmqpSession
     */
    protected function createTransportAmqpSessionMock()
    {
        return $this->getMock(TransportAmqpSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpConnection
     */
    protected function createAmqpConnectionMock()
    {
        return $this->getMock(AmqpConnection::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportNullSession
     */
    protected function createTransportNullSessionMock()
    {
        return $this->getMock(TransportNullSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NullConnection
     */
    protected function createNullConnectionMock()
    {
        return $this->getMock(NullConnection::class, [], [], '', false);
    }
}
