<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\CommandExecutor;
use Oro\Bundle\EntityExtendBundle\Migration\LoadEntityConfigStateMigration;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;

/**
 * Fulfills gaps of extend entity configuration recieved from schema updates and refreshes corresponding cache.
 */
class RefreshExtendCachePostUpMigrationListener
{
    /** @var CommandExecutor */
    protected $commandExecutor;

    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $initialEntityConfigStatePath;

    /**
     * @param CommandExecutor $commandExecutor
     * @param ConfigManager   $configManager
     * @param string          $initialEntityConfigStatePath
     */
    public function __construct(
        CommandExecutor $commandExecutor,
        ConfigManager $configManager,
        $initialEntityConfigStatePath
    ) {
        $this->commandExecutor              = $commandExecutor;
        $this->configManager                = $configManager;
        $this->initialEntityConfigStatePath = $initialEntityConfigStatePath;
    }

    /**
     * PRE UP event handler
     */
    public function onPreUp(PreMigrationEvent $event)
    {
        $event->addMigration(
            new LoadEntityConfigStateMigration()
        );
    }
}
