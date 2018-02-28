<?php

namespace Oro\Bundle\SearchBundle\EventListener\Command;

use Oro\Bundle\InstallerBundle\InstallerEvent;

/**
 * Allows to run search re-indexation command
 */
trait ReindexationCommandTrait
{
    /**
     * @param InstallerEvent $event
     * @param string $commandName
     * @param bool $isScheduled
     * @param bool $processIsolation
     */
    protected function executeReindexation(
        InstallerEvent $event,
        string $commandName,
        bool $isScheduled = false,
        bool $processIsolation = true
    ) {
        $params = [];
        if ($isScheduled) {
            $params['--scheduled'] = true;
        }
        if ($processIsolation) {
            $params['--process-isolation'] = true;
        }

        $commandExecutor = $event->getCommandExecutor();
        $commandExecutor->runCommand($commandName, $params);
    }
}
