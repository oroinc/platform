<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Oro\Bundle\PlatformBundle\Command\OptionalListenersCommand;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;

class OptionalListenersOptionsListener extends AddGlobalOptionsListener
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $options = [
            new InputOption(
                OptionalListenersListener::DISABLE_OPTIONAL_LISTENERS,
                null,
                InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
                sprintf(
                    'Disable optional listeners, "%s" to disable all listeners, '
                    .'command "%s" shows all listeners',
                    OptionalListenersListener::ALL_OPTIONAL_LISTENERS_VALUE,
                    OptionalListenersCommand::NAME
                )
            ),
        ];

        $this->addOptionsToCommand($event->getCommand(), $options);
    }
}
