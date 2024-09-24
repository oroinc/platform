<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutor;

abstract class MigrationExecutorTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var ArrayLogger */
    protected $logger;

    /** @var MigrationQueryExecutor */
    protected $queryExecutor;

    /** @var OroDataCacheManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $sm = $this->createMock(AbstractSchemaManager::class);
        $sm->expects(self::once())
            ->method('listTables')
            ->willReturn($this->getTables());
        $sm->expects(self::once())
            ->method('createSchemaConfig')
            ->willReturn(null);
        $this->connection->expects(self::atLeastOnce())
            ->method('getSchemaManager')
            ->willReturn($sm);
        $this->connection->expects(self::atLeastOnce())
            ->method('getDatabasePlatform')
            ->willReturn(new MySqlPlatform());

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
