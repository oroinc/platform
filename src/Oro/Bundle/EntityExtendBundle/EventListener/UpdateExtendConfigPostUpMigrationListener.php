<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\UpdateExtendConfigMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class UpdateExtendConfigPostUpMigrationListener
{
    /**
     * @var CommandExecutor
     */
    protected $commandExecutor;

    /**
     * @var string
     */
    protected $configProcessorOptionsPath;

    /**
     * @param CommandExecutor $commandExecutor
     * @param string          $configProcessorOptionsPath
     */
    public function __construct(CommandExecutor $commandExecutor, $configProcessorOptionsPath)
    {
        $this->commandExecutor            = $commandExecutor;
        $this->configProcessorOptionsPath = $configProcessorOptionsPath;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateExtendConfigMigration(
                $this->commandExecutor,
                $this->configProcessorOptionsPath
            )
        );
    }
}
