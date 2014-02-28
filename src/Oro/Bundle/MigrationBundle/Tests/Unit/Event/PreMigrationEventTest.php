<?php

namespace Oro\Bundle\MigrationBundle\Tests\Unit\Event;

use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;

class PreMigrationEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PreMigrationEvent
     */
    protected $preMigrationEvent;

    public function setUp()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
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
