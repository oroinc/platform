<?php

namespace Oro\Bundle\CronBundle\EventListener;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\FeatureToggleBundle\EventListener\ConsoleCommandListener as BaseListener;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Disables a command when it is a part of some feature and this feature is disabled.
 */
class ConsoleCommandListener extends BaseListener
{
    /**
     * {@inheritDoc}
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if ($event->getCommand() instanceof CronCommandScheduleDefinitionInterface) {
            return;
        }

        parent::onConsoleCommand($event);
    }
}
