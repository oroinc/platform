<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\MigrationQueryExecutor;

class AbstractTestMigrationExecutor extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    /** @var ArrayLogger */
    protected $logger;

    /** @var MigrationQueryExecutor */
    protected $queryExecutor;

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
        $this->connection->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($platform));

        $this->logger = new ArrayLogger();

        $this->queryExecutor = new MigrationQueryExecutor($this->connection);
        $this->queryExecutor->setLogger($this->logger);
    }

    /**
     * @param string $filePath
     */
    protected function includeFile($filePath)
    {
        $fileName = __DIR__ . '/../Fixture/src/TestPackage/src/' . $filePath;
        $this->assertFileExists($fileName);
        include_once $fileName;
    }

    /**
     * @return array
     */
    protected function getTables()
    {
        return [];
    }
}
