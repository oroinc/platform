<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Migration\WarmUpEntityConfigCacheMigration;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListener
{
    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @param CommandExecutor $commandExecutor
     */
    public function __construct(CommandExecutor $commandExecutor)
    {
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * Registers a migration to update entity configs
     *
     * @param PostMigrationEvent $event
     */
    public function updateConfigs(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateEntityConfigMigration($this->commandExecutor)
        );
    }

    /**
     * Registers a migration to warm up entity configs cache
     *
     * @param PostMigrationEvent $event
     */
    public function warmUpCache(PostMigrationEvent $event)
    {
        $event->addMigration(
            new WarmUpEntityConfigCacheMigration($this->commandExecutor)
        );
    }
}
