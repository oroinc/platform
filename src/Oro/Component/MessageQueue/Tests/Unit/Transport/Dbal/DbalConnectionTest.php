<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use PHPUnit\Framework\TestCase;

class DbalConnectionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments(): void
    {
        new DbalConnection($this->createMock(Connection::class), 'table');
    }

    public function testShouldCreateSessionInstance(): void
    {
        $connection = new DbalConnection($this->createMock(Connection::class), 'table');

        $this->assertInstanceOf(DbalSession::class, $connection->createSession());
    }

    public function testShouldReturnDBALConnectionInstance(): void
    {
        $connection = new DbalConnection($this->createMock(Connection::class), 'table');

        $this->assertInstanceOf(Connection::class, $connection->getDBALConnection());
    }

    public function testShouldReturnTableName(): void
    {
        $connection = new DbalConnection($this->createMock(Connection::class), 'table');

        $this->assertEquals('table', $connection->getTableName());
    }

    public function testShouldCloseConnection(): void
    {
        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects($this->once())
            ->method('close');

        $connection = new DbalConnection($dbalConnection, 'table');
        $connection->close();
    }
}
