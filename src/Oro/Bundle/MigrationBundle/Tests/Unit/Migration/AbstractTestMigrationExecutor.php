<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutor;

class AbstractTestMigrationExecutor extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var ArrayLogger */
    protected $logger;

    /** @var MigrationQueryExecutor */
    protected $queryExecutor;

    /** @var OroDataCacheManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheManager;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $platform = new MySqlPlatform();
        $sm = $this->createMock(AbstractSchemaManager::class);
        $sm->expects($this->once())
            ->method('listTables')
            ->willReturn($this->getTables());
        $sm->expects($this->once())
            ->method('createSchemaConfig')
            ->willReturn(null);
        $this->connection->expects($this->atLeastOnce())
            ->method('getSchemaManager')
            ->willReturn($sm);
        $this->connection->expects($this->atLeastOnce())
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        $this->logger = new ArrayLogger();

        $this->queryExecutor = new MigrationQueryExecutor($this->connection);
        $this->queryExecutor->setLogger($this->logger);

        $this->cacheManager = $this->createMock(OroDataCacheManager::class);
    }

    /**
     * @return Table[]
     */
    protected function getTables(): array
    {
        return [];
    }
}
