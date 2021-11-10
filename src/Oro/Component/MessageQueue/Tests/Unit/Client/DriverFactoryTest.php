<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\DriverFactory;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;

class DriverFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate(): void
    {
        $config = new Config('', '');

        /** @var Connection|\PHPUnit\Framework\MockObject\MockObject $doctrineConnection */
        $doctrineConnection = $this->createMock(Connection::class);
        $connection = new DbalConnection($doctrineConnection, 'aTableName');

        $factory = new DriverFactory([DbalConnection::class => DbalDriver::class]);
        $driver = $factory->create($connection, $config);

        self::assertInstanceOf(DbalDriver::class, $driver);
        self::assertSame($config, $driver->getConfig());
    }

    public function testCreateLogicException(): void
    {
        $factory = new DriverFactory([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected connection instance: "Mock_Connection');

        /** @var ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $factory->create($connection, new Config('', ''));
    }
}
