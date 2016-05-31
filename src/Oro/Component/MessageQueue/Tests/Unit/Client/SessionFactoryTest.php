<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Oro\Component\MessageQueue\Transport\Null\NullSession as TransportNullSession;
use Oro\Component\MessageQueue\Client\AmqpSession;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\NullSession;
use Oro\Component\MessageQueue\Client\SessionFactory;

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
        $this->setExpectedException(\LogicException::class, 'Unexpected connection instance: "Mock_Connection');

        SessionFactory::create($this->getMock(ConnectionInterface::class), new Config('', '', '', ''));
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
