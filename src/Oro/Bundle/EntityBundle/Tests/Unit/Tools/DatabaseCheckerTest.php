<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;

class DatabaseCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
    }

    public function testCheckDatabaseForInstalledApplication()
    {
        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], '2017-01-01');

        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');

        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabase()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], '2017-01-01');
        $databaseChecker->clearCheckDatabase();
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplication()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], null);
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndTablesDoNotExist()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], false);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], null);
        self::assertFalse($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertFalse($databaseChecker->checkDatabase());
    }

    private function setTablesExistExpectation(array $tables, bool $result): Connection
    {
        $connection = $this->createMock(Connection::class);
        $schemaManager = $this->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tablesExist'])
            ->getMockForAbstractClass();
        $connection->expects(self::once())
            ->method('connect');
        $connection->expects(self::once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->expects(self::once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($result);

        return $connection;
    }
}
