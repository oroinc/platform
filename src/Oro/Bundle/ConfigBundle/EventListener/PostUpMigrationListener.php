<?php

namespace Oro\Bundle\ConfigBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class PostUpMigrationListener
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Clear system configuration cache
     *
     * @param PostMigrationEvent $event
     */
    public function updateConfigs(PostMigrationEvent $event)
    {
        $this->configManager->clearCache();
    }
}
