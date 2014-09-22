<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Migration\RefreshExtendCacheMigration;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class RefreshExtendCachePostUpMigrationListener
{
    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @param CommandExecutor $commandExecutor
     * @param ConfigManager $configManager
     */
    public function __construct(CommandExecutor $commandExecutor, ConfigManager $configManager)
    {
        $this->commandExecutor = $commandExecutor;
        $this->configManager = $configManager;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new RefreshExtendCacheMigration($this->commandExecutor, $this->configManager)
        );
    }
}
