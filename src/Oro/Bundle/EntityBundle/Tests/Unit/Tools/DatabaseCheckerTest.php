<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use PHPUnit\Framework\MockObject\MockObject;

class DatabaseCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
    }

    public function testCheckDatabaseForInstalledApplication()
    {
        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], '2017-01-01');

        // test that the result is cached
        $this->doctrine->expects(static::never())->method('getConnection');

        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabase()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())->method('getConnection')->willReturn($connection);

        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], '2017-01-01');
        $databaseChecker->clearCheckDatabase();
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplication()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())->method('getConnection')->willReturn($connection);

        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], null);
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndTablesDoNotExist()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], false);
        $this->doctrine->expects(self::once())->method('getConnection')->willReturn($connection);

        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], null);
        self::assertFalse($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())->method('getConnection');
        self::assertFalse($databaseChecker->checkDatabase());
    }

    /**
     * @param string[] $tables
     * @param bool     $result
     *
     * @return MockObject|Connection
     */
    protected function setTablesExistExpectation($tables, $result)
    {
        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $schemaManager = $this->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tablesExist'])
            ->getMockForAbstractClass();
        $connection->expects(static::once())->method('connect');
        $connection->expects(static::once())->method('getSchemaManager')->willReturn($schemaManager);
        $schemaManager->expects(static::once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($result);

        return $connection;
    }
}
