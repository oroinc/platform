<?php

namespace Oro\Bundle\CronBundle\EventListener;

use Oro\Bundle\CronBundle\Command\CronCommandFeatureCheckerInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Disables a CRON command when it is a part of some feature and this feature is disabled.
 */
class CronCommandListener
{
    private CronCommandFeatureCheckerInterface $commandFeatureChecker;

    public function __construct(CronCommandFeatureCheckerInterface $commandFeatureChecker)
    {
        $this->commandFeatureChecker = $commandFeatureChecker;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command instanceof CronCommandScheduleDefinitionInterface
            && !$this->commandFeatureChecker->isFeatureEnabled($command->getName())
        ) {
            $event->disableCommand();
            $event->getOutput()->writeln('<error>The feature that enables this CRON command is turned off.</error>');
        }
    }
}
