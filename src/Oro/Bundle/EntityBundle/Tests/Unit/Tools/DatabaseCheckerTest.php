<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;

class DatabaseCheckerTest extends \PHPUnit\Framework\TestCase
{
    public function testCheckDatabaseForInstalledApplication()
    {
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $databaseChecker = new DatabaseChecker($doctrine, ['test_table'], '2017-01-01');
        self::assertTrue($databaseChecker->checkDatabase());
        self::assertAttributeSame(null, 'dbCheck', $databaseChecker);
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabase()
    {
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $databaseChecker = new DatabaseChecker($doctrine, ['test_table'], '2017-01-01');
        $databaseChecker->clearCheckDatabase();
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        self::assertAttributeSame(true, 'dbCheck', $databaseChecker);
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplication()
    {
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $databaseChecker = new DatabaseChecker($doctrine, ['test_table'], null);
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        self::assertAttributeSame(true, 'dbCheck', $databaseChecker);
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndTablesDoNotExist()
    {
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->setTablesExistExpectation(['test_table'], false);
        $doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $databaseChecker = new DatabaseChecker($doctrine, ['test_table'], null);
        self::assertFalse($databaseChecker->checkDatabase());
        // test that the result is cached
        self::assertAttributeSame(false, 'dbCheck', $databaseChecker);
        self::assertFalse($databaseChecker->checkDatabase());
    }

    public function testClearCheckDatabase()
    {
        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $databaseChecker = new DatabaseChecker($doctrine, ['test_table'], '2017-01-01');
        $databaseChecker->clearCheckDatabase();
        self::assertAttributeSame(null, 'dbCheck', $databaseChecker);
        self::assertAttributeSame(false, 'installed', $databaseChecker);
    }

    /**
     * @param string[] $tables
     * @param bool     $result
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|Connection
     */
    protected function setTablesExistExpectation($tables, $result)
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $schemaManager = $this->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['tablesExist'])
            ->getMockForAbstractClass();
        $connection->expects($this->once())
            ->method('connect');
        $connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($result);

        return $connection;
    }
}
