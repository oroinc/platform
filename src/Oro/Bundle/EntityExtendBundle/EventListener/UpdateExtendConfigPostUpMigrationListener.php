<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Handles post-migration updates for extended entity configurations.
 *
 * This listener is triggered after database migrations are applied. It schedules a migration
 * to update and synchronize extended entity configurations based on the initial entity config
 * state and current processor options, ensuring that all extend configurations are properly
 * applied and consistent across the application.
 */
class UpdateExtendConfigPostUpMigrationListener
{
    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var string */
    protected $configProcessorOptionsPath;

    /** @var string */
    protected $initialEntityConfigStatePath;

    /**
     * @param CommandExecutor $commandExecutor
     * @param string          $configProcessorOptionsPath
     * @param string          $initialEntityConfigStatePath
     */
    public function __construct(
        CommandExecutor $commandExecutor,
        $configProcessorOptionsPath,
        $initialEntityConfigStatePath
    ) {
        $this->commandExecutor              = $commandExecutor;
        $this->configProcessorOptionsPath   = $configProcessorOptionsPath;
        $this->initialEntityConfigStatePath = $initialEntityConfigStatePath;
    }

    /**
     * POST UP event handler
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendConfigMigration(
                $this->commandExecutor,
                $this->configProcessorOptionsPath,
                $this->initialEntityConfigStatePath
            )
        );
    }
}
