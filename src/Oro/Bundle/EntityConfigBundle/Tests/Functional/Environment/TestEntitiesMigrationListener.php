<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Adds several extended attributes to TestActivityTarget entity to use in functional tests.
 * @see \Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadAttributeData
 */
class TestEntitiesMigrationListener
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new AddAttributesToTestActivityTargetMigration());
    }

    /**
     * @param PostMigrationEvent $event
     */
    public function updateAttributes(PostMigrationEvent $event)
    {
        $event->addMigration(new UpdateAttributesForTestActivityTargetMigration($this->configManager));
    }
}
