<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityBundle\Tools\CheckDatabaseStateManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class ConsoleCommandListener
{
    /** @var CheckDatabaseStateManager */
    private $checkDatabaseStateManager;

    /**
     * @param CheckDatabaseStateManager $checkDatabaseStateManager
     */
    public function __construct(CheckDatabaseStateManager $checkDatabaseStateManager)
    {
        $this->checkDatabaseStateManager = $checkDatabaseStateManager;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if (CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)
            || CommandExecutor::isCurrentCommand('oro:platform:upgrade20', true)
        ) {
            $this->checkDatabaseStateManager->clearState();
        }
    }
}
