<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
use Oro\Bundle\EntityConfigBundle\Migration\WarmUpEntityConfigCacheMigration;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Entity config update preUp, postUp migrations setup.
 */
class PostUpMigrationListener
{
    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    public function __construct(CommandExecutor $commandExecutor)
    {
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * Registers a migration to update entity configs
     */
    public function updateConfigs(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateEntityConfigMigration($this->commandExecutor)
        );
    }

    /**
     * Registers a migration to warm up entity configs cache
     */
    public function warmUpCache(PostMigrationEvent $event)
    {
        $event->addMigration(
            new WarmUpEntityConfigCacheMigration($this->commandExecutor)
        );
    }
}
