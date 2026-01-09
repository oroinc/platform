<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatabaseCheckerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private ApplicationState&MockObject $applicationState;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->applicationState = $this->createMock(ApplicationState::class);
    }

    public function testCheckDatabaseForInstalledApplication(): void
    {
        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(true);
        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], $this->applicationState);

        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');

        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForInstalledApplicationAfterCallClearCheckDatabase(): void
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->applicationState->expects(self::never())
            ->method('isInstalled');
        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], $this->applicationState);
        $databaseChecker->clearCheckDatabase();
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplication(): void
    {
        $connection = $this->setTablesExistExpectation(['test_table'], true);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);
        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], $this->applicationState);
        self::assertTrue($databaseChecker->checkDatabase());
        // test that the result is cached
        $this->doctrine->expects(self::never())
            ->method('getConnection');
        self::assertTrue($databaseChecker->checkDatabase());
    }

    public function testCheckDatabaseForNotInstalledApplicationAndTablesDoNotExist(): void
    {
        $connection = $this->setTablesExistExpectation(['test_table'], false);
        $this->doctrine->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $this->applicationState->expects(self::once())
            ->method('isInstalled')
            ->willReturn(false);

        $databaseChecker = new DatabaseChecker($this->doctrine, ['test_table'], $this->applicationState);
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
            ->method('createSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->expects(self::once())
            ->method('tablesExist')
            ->with($tables)
            ->willReturn($result);

        return $connection;
    }
}
