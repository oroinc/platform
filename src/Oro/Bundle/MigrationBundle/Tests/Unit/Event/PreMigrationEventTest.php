<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Event;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;

class PreMigrationEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreMigrationEvent */
    private $preMigrationEvent;

    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);

        $this->preMigrationEvent = new PreMigrationEvent($connection);
    }

    public function testLoadedVersions()
    {
        $this->preMigrationEvent->setLoadedVersion('testBundle', 'v1_0');
        $this->assertEquals(['testBundle' => 'v1_0'], $this->preMigrationEvent->getLoadedVersions());
        $this->assertEquals('v1_0', $this->preMigrationEvent->getLoadedVersion('testBundle'));
        $this->assertNull($this->preMigrationEvent->getLoadedVersion('nonLoggedBundle'));
    }
}
