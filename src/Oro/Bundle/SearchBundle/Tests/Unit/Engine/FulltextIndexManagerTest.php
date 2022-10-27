<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\FulltextIndexManager;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;

class FulltextIndexManagerTest extends \PHPUnit\Framework\TestCase
{
    private const TABLE_NAME = 'oro_test_table';
    private const INDEX_NAME = 'oro_test_table_value_idx';

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var FulltextIndexManager */
    private $indexManager;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $config = [
            DatabaseDriverInterface::DRIVER_MYSQL => PdoMysql::class
        ];

        $this->indexManager = new FulltextIndexManager($this->connection, $config, self::TABLE_NAME, self::INDEX_NAME);
    }

    public function testCreateIndexes()
    {
        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(
                ['driver' => DatabaseDriverInterface::DRIVER_MYSQL]
            );

        $this->connection->expects($this->once())
            ->method('query')
            ->with(PdoMysql::getPlainSql(self::TABLE_NAME, self::INDEX_NAME));

        $this->assertTrue($this->indexManager->createIndexes());
    }

    public function testCreateIndexWithError()
    {
        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(
                ['driver' => DatabaseDriverInterface::DRIVER_MYSQL]
            );

        $this->connection->expects($this->once())
            ->method('query')
            ->willThrowException(new DBALException());

        $this->assertFalse($this->indexManager->createIndexes());
    }

    public function testGetQueryForUnknownDriver()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Driver "pdo_pgsql" not found');

        $this->connection->expects($this->once())
            ->method('getParams')
            ->willReturn(
                ['driver' => DatabaseDriverInterface::DRIVER_POSTGRESQL]
            );

        $this->indexManager->getQuery();
    }
}
