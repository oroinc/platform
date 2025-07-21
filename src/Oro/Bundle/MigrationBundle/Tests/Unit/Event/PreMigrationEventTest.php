<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Event;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use PHPUnit\Framework\TestCase;

class PreMigrationEventTest extends TestCase
{
    private PreMigrationEvent $preMigrationEvent;

    #[\Override]
    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);

        $this->preMigrationEvent = new PreMigrationEvent($connection);
    }

    public function testLoadedVersions(): void
    {
        $this->preMigrationEvent->setLoadedVersion('testBundle', 'v1_0');
        $this->assertEquals(['testBundle' => 'v1_0'], $this->preMigrationEvent->getLoadedVersions());
        $this->assertEquals('v1_0', $this->preMigrationEvent->getLoadedVersion('testBundle'));
        $this->assertNull($this->preMigrationEvent->getLoadedVersion('nonLoggedBundle'));
    }
}
