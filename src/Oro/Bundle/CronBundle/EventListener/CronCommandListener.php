<?php

namespace Oro\Bundle\CronBundle\EventListener;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Disables a CRON command when it is a part of some feature and this feature is disabled.
 */
class CronCommandListener
{
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if (!$command instanceof CronCommandInterface) {
            return;
        }

        if (!$command->isActive()) {
            $event->disableCommand();
            $event->getOutput()->writeln('<error>This CRON command is disabled.</error>');
        }
    }
}
