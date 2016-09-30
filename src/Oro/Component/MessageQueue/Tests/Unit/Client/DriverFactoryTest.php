<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\DriverFactory;
use Oro\Component\MessageQueue\Client\NullDriver;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSchema;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Oro\Component\MessageQueue\Transport\Null\NullSession;

class DriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldCreateNullSessionInstance()
    {
        $config = new Config('', '', '', '');
        $connection = new NullConnection();

        $factory = new DriverFactory([NullConnection::class => NullDriver::class]);
        $driver = $factory->create($connection, $config);

        self::assertInstanceOf(NullDriver::class, $driver);
        self::assertAttributeInstanceOf(NullSession::class, 'session', $driver);
        self::assertAttributeSame($config, 'config', $driver);
    }

    public function testShouldCreateDbalSessionInstance()
    {
        $config = new Config('', '', '', '');

        $doctrineConnection = $this->getMock(Connection::class, [], [], '', false);
        $connection = new DbalConnection($doctrineConnection, 'aTableName');

        $factory = new DriverFactory([DbalConnection::class => DbalDriver::class]);
        $driver = $factory->create($connection, $config);

        self::assertInstanceOf(DbalDriver::class, $driver);
        self::assertAttributeInstanceOf(DbalSession::class, 'session', $driver);
        self::assertAttributeSame($config, 'config', $driver);
    }

    public function testShouldThrowExceptionIfUnexpectedConnectionInstance()
    {
        $factory = new DriverFactory([]);

        $this->setExpectedException(\LogicException::class, 'Unexpected connection instance: "Mock_Connection');
        $factory->create($this->getMock(ConnectionInterface::class), new Config('', '', '', ''));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NullSession
     */
    protected function createNullSessionMock()
    {
        return $this->getMock(NullSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|NullConnection
     */
    protected function createNullConnectionMock()
    {
        return $this->getMock(NullConnection::class, [], [], '', false);
    }
}
