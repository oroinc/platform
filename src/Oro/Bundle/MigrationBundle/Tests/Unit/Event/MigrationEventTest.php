<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Event;

use Oro\Bundle\MigrationBundle\Event\MigrationEvent;

class MigrationEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MigrationEvent
     */
    protected $migrationEvent;

    protected $connection;

    public function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->migrationEvent = new MigrationEvent($this->connection);
    }

    public function testMigrationData()
    {
        $middleMigration = $this->getMockForAbstractClass('Oro\Bundle\MigrationBundle\Migration\Migration');
        $this->migrationEvent->addMigration($middleMigration);
        $firstMigration = $this->getMockForAbstractClass('Oro\Bundle\MigrationBundle\Migration\Migration');
        $this->migrationEvent->addMigration($firstMigration, true);
        $lastMigration = $this->getMockForAbstractClass('Oro\Bundle\MigrationBundle\Migration\Migration');
        $this->migrationEvent->addMigration($lastMigration);

        $migrations = $this->migrationEvent->getMigrations();
        $this->assertEquals(3, count($migrations));
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
            ->will($this->returnValue([]));
        $this->migrationEvent->getData($sql, $params, $types);
    }
}
