<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;

abstract class AddGlobalOptionsListener
{
    /**
     * @param ConsoleCommandEvent $event
     */
    abstract public function onConsoleCommand(ConsoleCommandEvent $event);

    /**
     * @param Command $command
     * @param array|InputOption[] $options
     */
    protected function addOptionsToCommand(Command $command, array $options)
    {
        $inputDefinition = $command->getApplication()->getDefinition();
        $commandDefinition = $command->getDefinition();

        foreach ($options as $option) {
            /**
             * Starting from Symfony 2.8 event 'ConsoleCommandEvent' fires after all definitions were merged.
             */
            $inputDefinition->addOption($option);
            $commandDefinition->addOption($option);
        }
    }
}
