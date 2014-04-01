<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigMigration;
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
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(
            new UpdateEntityConfigMigration($this->commandExecutor)
        );
    }
}
