<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;

class DbalConnectionTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DbalConnection($this->createMock(Connection::class), 'table');
    }

    public function testShouldCreateSessionInstance()
    {
        $connection = new DbalConnection($this->createMock(Connection::class), 'table');

        $this->assertInstanceOf(DbalSession::class, $connection->createSession());
    }

    public function testShouldReturnDBALConnectionInstance()
    {
        $connection = new DbalConnection($this->createMock(Connection::class), 'table');

        $this->assertInstanceOf(Connection::class, $connection->getDBALConnection());
    }

    public function testShouldReturnTableName()
    {
        $connection = new DbalConnection($this->createMock(Connection::class), 'table');

        $this->assertEquals('table', $connection->getTableName());
    }

    public function testShouldCloseConnection()
    {
        $dbalConnection = $this->createMock(Connection::class);
        $dbalConnection->expects($this->once())
            ->method('close');

        $connection = new DbalConnection($dbalConnection, 'table');
        $connection->close();
    }
}
