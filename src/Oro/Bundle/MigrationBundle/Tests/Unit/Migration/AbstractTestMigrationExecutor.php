<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutor;

class AbstractTestMigrationExecutor extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var ArrayLogger */
    protected $logger;

    /** @var MigrationQueryExecutor */
    protected $queryExecutor;

    /** @var OroDataCacheManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheManager;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $platform = new MySqlPlatform();
        $sm       = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->disableOriginalConstructor()
            ->setMethods(['listTables', 'createSchemaConfig'])
            ->getMockForAbstractClass();
        $sm->expects($this->once())
            ->method('listTables')
            ->will($this->returnValue($this->getTables()));
        $sm->expects($this->once())
            ->method('createSchemaConfig')
            ->will($this->returnValue(null));
        $this->connection->expects($this->atLeastOnce())
            ->method('getSchemaManager')
            ->will($this->returnValue($sm));
        $this->connection->expects($this->atLeastOnce())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $this->logger = new ArrayLogger();

        $this->queryExecutor = new MigrationQueryExecutor($this->connection);
        $this->queryExecutor->setLogger($this->logger);

        $this->cacheManager = $this->getMockBuilder('Oro\Bundle\CacheBundle\Manager\OroDataCacheManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return array
     */
    protected function getTables()
    {
        return [];
    }
}
