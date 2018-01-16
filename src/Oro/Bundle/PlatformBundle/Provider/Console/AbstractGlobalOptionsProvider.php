<?php

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractGlobalOptionsProvider implements GlobalOptionsProviderInterface
{
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
