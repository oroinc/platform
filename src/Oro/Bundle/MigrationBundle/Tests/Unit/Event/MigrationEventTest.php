<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Event;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Event\MigrationEvent;
use Oro\Bundle\MigrationBundle\Migration\Migration;

class MigrationEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var MigrationEvent */
    private $migrationEvent;

    private $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->migrationEvent = new MigrationEvent($this->connection);
    }

    public function testMigrationData()
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

    public function testGetData()
    {
        $sql = 'select * from test_table';
        $params = [];
        $types = [];

        $this->connection->expects($this->once())
            ->method('fetchAll')
            ->with($sql, $params, $types)
            ->willReturn([]);
        $this->migrationEvent->getData($sql, $params, $types);
    }
}
