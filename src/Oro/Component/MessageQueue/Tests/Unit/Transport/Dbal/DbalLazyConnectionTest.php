<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Dbal;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Dbal\DbalLazyConnection;
use PHPUnit\Framework\MockObject\MockObject;

class DbalLazyConnectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var DbalLazyConnection */
    private $connection;

    /** @var ManagerRegistry|MockObject */
    private $registry;

    /** @var Connection|MockObject */
    private $dbalConnection;

    protected function setUp(): void
    {
        $this->dbalConnection = $this->createMock(Connection::class);
        $this->dbalConnection->method('getSchemaManager')->willReturn($this->createMock(AbstractSchemaManager::class));

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->connection = new DbalLazyConnection($this->registry, 'theConnection', 'table');
    }

    public function testShouldImplementConnectionInterface()
    {
        static::assertInstanceOf(DbalConnection::class, $this->connection);
    }

    public function testShouldNotInitializeOnCreateSession()
    {
        $this->registry->expects(static::never())->method('getConnection');

        $session = $this->connection->createSession();

        static::assertInstanceOf(DbalLazyConnection::class, $session->getConnection());
    }

    public function testShouldInitializeOnGetDBALConnection()
    {
        $this->registry->expects(static::once())
            ->method('getConnection')
            ->with('theConnection')
            ->willReturn($this->dbalConnection);

        $connection = $this->connection->getDBALConnection();

        static::assertSame($this->dbalConnection, $connection);
    }

    public function testShouldNotInitializeOnGetDBALConnectionIfAlreadyInitialized()
    {
        $this->registry->expects(static::once())
            ->method('getConnection')
            ->with('theConnection')
            ->willReturn($this->dbalConnection);

        $connection1 = $this->connection->getDBALConnection();
        static::assertSame($this->dbalConnection, $connection1);

        $this->registry->expects(static::never())->method('getConnection');
        $connection2 = $this->connection->getDBALConnection();
        static::assertSame($this->dbalConnection, $connection2);
    }

    public function testShouldNotInitializeOnGetTableName()
    {
        $this->registry->expects(static::never())->method('getConnection');

        $this->connection->getTableName();
    }

    public function testShouldNotInitializeOnGetOptions()
    {
        $this->registry->expects(static::never())->method('getConnection');

        $this->connection->getOptions();
    }

    public function testShouldNotCallCloseIfNotInitialized()
    {
        $this->dbalConnection->expects(static::never())->method('close');
        $this->connection->close();
    }

    public function testShouldCallCloseIfAlreadyInitialized()
    {
        $this->registry->expects(static::once())
            ->method('getConnection')
            ->with('theConnection')
            ->willReturn($this->dbalConnection);
        $this->connection->getDBALConnection();

        $this->dbalConnection->expects(static::once())->method('close');

        $this->connection->close();
    }
}
