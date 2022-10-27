<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\EventListener;

use Doctrine\DBAL\Connection;
use Oro\Bundle\ActivityListBundle\EventListener\ActivityListPostUpMigrationListener;
use Oro\Bundle\ActivityListBundle\Migration\ActivityListMigration;
use Oro\Bundle\ActivityListBundle\Migration\Extension\ActivityListExtension;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class ActivityListPostUpMigrationListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnPostUp()
    {
        $provider = $this->createMock(ActivityListChainProvider::class);
        $activityListExtension = $this->createMock(ActivityListExtension::class);
        $metadataHelper = $this->createMock(EntityMetadataHelper::class);
        $nameGenerator = $this->createMock(ExtendDbIdentifierNameGenerator::class);
        $configManager = $this->createMock(ConfigManager::class);

        $listener = new ActivityListPostUpMigrationListener(
            $provider,
            $activityListExtension,
            $metadataHelper,
            $nameGenerator,
            $configManager
        );
        $connection = $this->createMock(Connection::class);

        $event = new PostMigrationEvent($connection);
        $listener->onPostUp($event);
        $migration = $event->getMigrations()[0];

        $this->assertInstanceOf(ActivityListMigration::class, $migration);
    }
}
