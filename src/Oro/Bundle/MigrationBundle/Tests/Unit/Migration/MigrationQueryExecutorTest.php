<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutor;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MigrationQueryExecutorTest extends TestCase
{
    private Connection&MockObject $connection;
    private LoggerInterface&MockObject $logger;
    private MigrationQueryExecutor $executor;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->executor = new MigrationQueryExecutor($this->connection);
        $this->executor->setLogger($this->logger);
    }

    public function testGetConnection(): void
    {
        $this->assertSame($this->connection, $this->executor->getConnection());
    }

    public function testExecuteSql(): void
    {
        $query = 'DELETE FROM some_table';

        $this->logger->expects($this->once())
            ->method('info')
            ->with($query);
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with($query);

        $this->executor->execute($query, false);
    }

    public function testExecuteSqlDryRun(): void
    {
        $query = 'DELETE FROM some_table';

        $this->logger->expects($this->once())
            ->method('info')
            ->with($query);
        $this->connection->expects($this->never())
            ->method('executeQuery');

        $this->executor->execute($query, true);
    }

    public function testExecuteMigrationQuery(): void
    {
        $query = $this->createMock(MigrationQuery::class);

        $query->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($this->logger));

        $this->executor->execute($query, false);
    }

    public function testExecuteConnectionAwareMigrationQuery(): void
    {
        $query = $this->createMock(ParametrizedMigrationQuery::class);

        $query->expects($this->once())
            ->method('setConnection')
            ->with($this->identicalTo($this->connection));
        $query->expects($this->once())
            ->method('execute')
            ->with($this->identicalTo($this->logger));

        $this->executor->execute($query, false);
    }

    public function testExecuteMigrationQueryDryRun(): void
    {
        $queryDescription = 'test query';

        $query = $this->createMock(MigrationQuery::class);

        $query->expects($this->once())
            ->method('getDescription')
            ->willReturn($queryDescription);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($queryDescription);

        $query->expects($this->never())
            ->method('execute');

        $this->executor->execute($query, true);
    }

    public function testExecuteMigrationQueryDryRunArrayDescription(): void
    {
        $queryDescription = ['test query 1', 'test query 2'];

        $query = $this->createMock(MigrationQuery::class);

        $query->expects($this->once())
            ->method('getDescription')
            ->willReturn($queryDescription);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [$queryDescription[0]],
                [$queryDescription[1]]
            );

        $query->expects($this->never())
            ->method('execute');

        $this->executor->execute($query, true);
    }

    public function testExecuteMigrationQueryDryRunEmptyDescription(): void
    {
        $queryDescription = null;

        $query = $this->createMock(MigrationQuery::class);

        $query->expects($this->once())
            ->method('getDescription')
            ->willReturn($queryDescription);

        $this->logger->expects($this->never())
            ->method('info');

        $query->expects($this->never())
            ->method('execute');

        $this->executor->execute($query, true);
    }
}
