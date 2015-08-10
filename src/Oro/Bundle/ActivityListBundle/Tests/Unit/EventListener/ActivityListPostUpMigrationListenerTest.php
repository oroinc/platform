<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActivityListBundle\EventListener\ActivityListPostUpMigrationListener;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class ActivityListPostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostUp()
    {
        $provider = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $activityListExtension = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $nameGenerator = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new ActivityListPostUpMigrationListener(
            $provider,
            $activityListExtension,
            $metadataHelper,
            $nameGenerator,
            $configManager
        );
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $event = new PostMigrationEvent($connection);
        $listener->onPostUp($event);
        $migration = $event->getMigrations()[0];

        $this->assertInstanceOf('Oro\Bundle\ActivityListBundle\Migration\ActivityListMigration', $migration);
    }
}
