<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;

class DbalLazyConnectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $dbalConnection;

    /** @var DbalLazyConnection */
    private $connection;

    protected function setUp(): void
    {
        $this->dbalConnection = $this->createMock(Connection::class);
        $this->dbalConnection->expects(self::any())
            ->method('getSchemaManager')
            ->willReturn($this->createMock(AbstractSchemaManager::class));

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->connection = new DbalLazyConnection($this->registry, 'theConnection', 'table');
    }

    public function testShouldImplementConnectionInterface()
    {
        self::assertInstanceOf(DbalConnection::class, $this->connection);
    }

    public function testShouldNotInitializeOnCreateSession()
    {
        $this->registry->expects(self::never())
            ->method('getConnection');

        $session = $this->connection->createSession();

        self::assertInstanceOf(DbalLazyConnection::class, $session->getConnection());
    }

    public function testShouldInitializeOnGetDBALConnection()
    {
        $this->registry->expects(self::once())
            ->method('getConnection')
            ->with('theConnection')
            ->willReturn($this->dbalConnection);

        $connection = $this->connection->getDBALConnection();

        self::assertSame($this->dbalConnection, $connection);
    }

    public function testShouldNotInitializeOnGetDBALConnectionIfAlreadyInitialized()
    {
        $this->registry->expects(self::once())
            ->method('getConnection')
            ->with('theConnection')
            ->willReturn($this->dbalConnection);

        $connection1 = $this->connection->getDBALConnection();
        self::assertSame($this->dbalConnection, $connection1);

        $this->registry->expects(self::never())
            ->method('getConnection');
        $connection2 = $this->connection->getDBALConnection();
        self::assertSame($this->dbalConnection, $connection2);
    }

    public function testShouldNotInitializeOnGetTableName()
    {
        $this->registry->expects(self::never())
            ->method('getConnection');

        $this->connection->getTableName();
    }

    public function testShouldNotInitializeOnGetOptions()
    {
        $this->registry->expects(self::never())
            ->method('getConnection');

        $this->connection->getOptions();
    }

    public function testShouldNotCallCloseIfNotInitialized()
    {
        $this->dbalConnection->expects(self::never())
            ->method('close');
        $this->connection->close();
    }

    public function testShouldCallCloseIfAlreadyInitialized()
    {
        $this->registry->expects(self::once())
            ->method('getConnection')
            ->with('theConnection')
            ->willReturn($this->dbalConnection);
        $this->connection->getDBALConnection();

        $this->dbalConnection->expects(self::once())
            ->method('close');

        $this->connection->close();
    }
}
