<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\Console\AddGlobalOptionsListener;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;

class ConsoleContextOptionsListener extends AddGlobalOptionsListener
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $options = [
            new InputOption(
                ConsoleContextListener::OPTION_USER,
                null,
                InputOption::VALUE_REQUIRED,
                'ID, username or email of the user that should be used as current user'
            ),
            new InputOption(
                ConsoleContextListener::OPTION_ORGANIZATION,
                null,
                InputOption::VALUE_REQUIRED,
                'ID or name of the organization that should be used as current organization'
            )
        ];

        $this->addOptionsToCommand($event->getCommand(), $options);
    }
}
