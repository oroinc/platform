<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\DatabaseConnectionsClearer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

class DatabaseConnectionsClearerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Container */
    private $container;

    /** @var DatabaseConnectionsClearer */
    private $clearer;

    protected function setUp()
    {
        $this->container = $this->createMock(Container::class);

        $this->clearer = new DatabaseConnectionsClearer(
            $this->container,
            ['foo_connection' => 'foo_connection_service_id']
        );
    }

    public function testClearShouldCloseConnectedConnections()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $connection = $this->createMock(Connection::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_connection_service_id')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_connection_service_id')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(true);
        $connection->expects(self::once())
            ->method('close');

        $logger->expects(self::once())
            ->method('info')
            ->with('Close database connection "foo_connection"');

        $this->clearer->clear($logger);
    }

    public function testClearShouldNotCloseNotConnectedConnections()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $connection = $this->createMock(Connection::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_connection_service_id')
            ->willReturn(true);
        $this->container->expects(self::once())
            ->method('get')
            ->with('foo_connection_service_id')
            ->willReturn($connection);
        $connection->expects(self::once())
            ->method('isConnected')
            ->willReturn(false);
        $connection->expects(self::never())
            ->method('close');

        $logger->expects(self::never())
            ->method('info');

        $this->clearer->clear($logger);
    }

    public function testClearShouldNotCloseNotInitializedConnections()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $this->container->expects(self::once())
            ->method('initialized')
            ->with('foo_connection_service_id')
            ->willReturn(false);
        $this->container->expects(self::never())
            ->method('get');

        $logger->expects(self::never())
            ->method('info');

        $this->clearer->clear($logger);
    }
}
