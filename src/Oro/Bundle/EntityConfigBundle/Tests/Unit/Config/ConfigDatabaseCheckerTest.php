<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigDatabaseChecker;
use Oro\Bundle\EntityConfigBundle\Config\LockObject;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigDatabaseCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    /** @var LockObject|MockObject */
    private $lockObject;

    protected function setUp(): void
    {
        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $this->lockObject = $this->getMockBuilder(LockObject::class)->disableOriginalConstructor()->getMock();
    }

    public function testCheckDatabaseForInstalledApplication()
    {
        $databaseChecker = new ConfigDatabaseChecker($this->lockObject, $this->doctrine, ['test_table'], '2017-01-01');
        $this->lockObject->expects(self::never())->method('isLocked');
        $this->doctrine->expects(self::never())->method('getConnection');

        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabaseAndConfigIsNotLocked()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())->method('getConnection')->willReturn($connection);
        $this->lockObject->expects(self::once())->method('isLocked')->willReturn(false);

        $databaseChecker = new ConfigDatabaseChecker($this->lockObject, $this->doctrine, ['test_table'], '2017-01-01');
        $databaseChecker->clearCheckDatabase();

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabaseAndConfigIsLocked()
    {
        $this->doctrine->expects(self::never())->method('getConnection');
        $this->lockObject->expects(self::exactly(2))->method('isLocked')->willReturn(true);

        $databaseChecker = new ConfigDatabaseChecker($this->lockObject, $this->doctrine, ['test_table'], '2017-01-01');
        $databaseChecker->clearCheckDatabase();

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is not cached
        $this->doctrine->expects(self::never())->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndConfigIsNotLocked()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())->method('getConnection')->willReturn($connection);
        $this->lockObject->expects(self::once())->method('isLocked')->willReturn(false);

        $databaseChecker = new ConfigDatabaseChecker($this->lockObject, $this->doctrine, ['test_table'], null);

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndConfigIsLocked()
    {
        $this->doctrine->expects(self::never())->method('getConnection');
        $this->lockObject->expects(self::exactly(2))->method('isLocked')->willReturn(true);

        $databaseChecker = new ConfigDatabaseChecker($this->lockObject, $this->doctrine, ['test_table'], null);

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is not cached
        $this->lockObject->expects(self::exactly(1))->method('isLocked')->willReturn(true);
        $this->doctrine->expects(self::never())->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndTablesDoNotExistAndConfigIsNotLocked()
    {
        $connection = $this->setTablesExistExpectation(['test_table'], false);
        $this->doctrine->expects(self::once())->method('getConnection')->willReturn($connection);
        $this->lockObject->expects(self::once())->method('isLocked')->willReturn(false);

        $databaseChecker = new ConfigDatabaseChecker($this->lockObject, $this->doctrine, ['test_table'], null);

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
        $schemaManager = $this->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tablesExist'])
            ->getMockForAbstractClass();

        $schemaManager->expects(static::once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($result);

        $connection = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $connection->expects(static::once())->method('connect');
        $connection->expects(static::once())->method('getSchemaManager')->willReturn($schemaManager);

        return $connection;
    }
}
