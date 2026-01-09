<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FulltextIndexManagerTest extends TestCase
{
    private const TABLE_NAME = 'oro_test_table';
    private const INDEX_NAME = 'oro_test_table_value_idx';

    private Connection&MockObject $connection;
    private FulltextIndexManager $indexManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $config = [
            DatabaseDriverInterface::DRIVER_MYSQL => PdoMysql::class
        ];

        $this->indexManager = new FulltextIndexManager($this->connection, $config, self::TABLE_NAME, self::INDEX_NAME);
    }

    public function testCreateIndexes(): void
    {
        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(
                ['driver' => DatabaseDriverInterface::DRIVER_MYSQL]
            );

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(PdoMysql::getPlainSql(self::TABLE_NAME, self::INDEX_NAME));

        $this->assertTrue($this->indexManager->createIndexes());
    }

    public function testCreateIndexWithError(): void
    {
        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(
                ['driver' => DatabaseDriverInterface::DRIVER_MYSQL]
            );

        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new Exception());

        $this->assertFalse($this->indexManager->createIndexes());
    }

    public function testGetQueryForUnknownDriver(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Driver "postgresql" not found');

        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(
                ['driver' => DatabaseDriverInterface::DRIVER_POSTGRESQL]
            );

        $this->indexManager->getQuery();
    }
}
