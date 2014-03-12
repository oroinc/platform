<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

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
     * @var mixed
     */
    protected $installed;

    /**
     * @param CommandExecutor $commandExecutor
     * @param mixed           $installed
     */
    public function __construct(CommandExecutor $commandExecutor, $installed)
    {
        $this->commandExecutor = $commandExecutor;
        $this->installed       = $installed;
    }

    /**
     * POST UP event handler
     *
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        if ($this->installed) {
            $event->addMigration(
                new RefreshExtendCacheMigration($this->commandExecutor)
            );
        }
    }
}
