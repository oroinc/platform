<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityConfigBundle\Config\ConfigDatabaseChecker;
use Oro\Bundle\EntityConfigBundle\Config\LockObject;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigDatabaseCheckerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private LockObject&MockObject $lockObject;
    private ApplicationState&MockObject $applicationState;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->lockObject = $this->createMock(LockObject::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
    }

    public function testCheckDatabaseForInstalledApplication(): void
    {
        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);
        $databaseChecker = new ConfigDatabaseChecker(
            $this->lockObject,
            $this->doctrine,
            ['test_table'],
            $this->applicationState
        );
        $this->lockObject->expects(self::never())
            ->method('isLocked');
        $this->doctrine->expects(self::never())
            ->method('getConnection');

        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabaseAndConfigIsNotLocked(): void
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->lockObject->expects(self::once())
            ->method('isLocked')
            ->willReturn(false);

        $this->applicationState->expects(self::never())
            ->method('isInstalled');
        $databaseChecker = new ConfigDatabaseChecker(
            $this->lockObject,
            $this->doctrine,
            ['test_table'],
            $this->applicationState
        );
        $databaseChecker->clearCheckDatabase();

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabaseAndConfigIsLocked(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        $this->lockObject->expects(self::exactly(2))
            ->method('isLocked')
            ->willReturn(true);

        $this->applicationState->expects(self::never())
            ->method('isInstalled');

        $databaseChecker = new ConfigDatabaseChecker(
            $this->lockObject,
            $this->doctrine,
            ['test_table'],
            $this->applicationState
        );
        $databaseChecker->clearCheckDatabase();

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is not cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndConfigIsNotLocked(): void
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->lockObject->expects(self::once())
            ->method('isLocked')
            ->willReturn(false);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $databaseChecker = new ConfigDatabaseChecker(
            $this->lockObject,
            $this->doctrine,
            ['test_table'],
            $this->applicationState
        );

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndConfigIsLocked(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        $this->lockObject->expects(self::exactly(2))
            ->method('isLocked')
            ->willReturn(true);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $databaseChecker = new ConfigDatabaseChecker(
            $this->lockObject,
            $this->doctrine,
            ['test_table'],
            $this->applicationState
        );

        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is not cached
        $this->lockObject->expects(self::once())
            ->method('isLocked')
            ->willReturn(true);
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndTablesDoNotExistAndConfigIsNotLocked(): void
    {
        $connection = $this->setTablesExistExpectation(['test_table'], false);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);
        $this->lockObject->expects(self::once())
            ->method('isLocked')
            ->willReturn(false);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $databaseChecker = new ConfigDatabaseChecker(
            $this->lockObject,
            $this->doctrine,
            ['test_table'],
            $this->applicationState
        );

        self::assertFalse($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertFalse($databaseChecker->checkDatabase());
    }

    private function setTablesExistExpectation(array $tables, bool $result): Connection
    {
        $schemaManager = $this->getMockBuilder(AbstractSchemaManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['tablesExist'])
            ->getMockForAbstractClass();

        $schemaManager->expects(self::once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($result);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())
            ->method('connect');
        $connection->expects(self::once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        return $connection;
    }
}
