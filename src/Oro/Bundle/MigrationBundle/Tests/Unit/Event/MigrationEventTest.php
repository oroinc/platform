<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Event;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Event\MigrationEvent;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use PHPUnit\Framework\TestCase;

class MigrationEventTest extends TestCase
{
    private MigrationEvent $migrationEvent;

    private $connection;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->migrationEvent = new MigrationEvent($this->connection);
    }

    public function testMigrationData(): void
    {
        $middleMigration = $this->getMockForAbstractClass(Migration::class);
        $this->migrationEvent->addMigration($middleMigration);
        $firstMigration = $this->getMockForAbstractClass(Migration::class);
        $this->migrationEvent->addMigration($firstMigration, true);
        $lastMigration = $this->getMockForAbstractClass(Migration::class);
        $this->migrationEvent->addMigration($lastMigration);

        $migrations = $this->migrationEvent->getMigrations();
        $this->assertCount(3, $migrations);
        $this->assertEquals($firstMigration, $migrations[0]);
        $this->assertEquals($middleMigration, $migrations[1]);
        $this->assertEquals($lastMigration, $migrations[2]);
    }

    public function testGetData(): void
    {
        $sql = 'select * from test_table';
        $params = [];
        $types = [];

        $this->connection->expects($this->once())
            ->method('fetchAllAssociative')
            ->with($sql, $params, $types)
            ->willReturn([]);
        $this->migrationEvent->getData($sql, $params, $types);
    }
}
