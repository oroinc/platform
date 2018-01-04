<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Symfony\Component\Console\Event\ConsoleCommandEvent;

class RebindDefinitionListener
{
    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        /**
         * Added only for compatibility with Symfony below 2.8
         */
        $command->mergeApplicationDefinition();
        $input->bind($command->getDefinition());
    }
}
