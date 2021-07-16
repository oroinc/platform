<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityBundle\Tools\CheckDatabaseStateManager;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Clears the state of all database checkers
 */
class ConsoleCommandListener
{
    /** @var CheckDatabaseStateManager */
    private $checkDatabaseStateManager;

    public function __construct(CheckDatabaseStateManager $checkDatabaseStateManager)
    {
        $this->checkDatabaseStateManager = $checkDatabaseStateManager;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        if (CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)) {
            $this->checkDatabaseStateManager->clearState();
        }
    }
}
