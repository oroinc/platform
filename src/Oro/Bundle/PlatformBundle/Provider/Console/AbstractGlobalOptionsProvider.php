<?php
declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Provider\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Base class for providers of options that are available for all console commands.
 */
abstract class AbstractGlobalOptionsProvider implements GlobalOptionsProviderInterface
{
    /**
     * @param Command $command
     * @param array|InputOption[] $options
     */
    protected function addOptionsToCommand(Command $command, array $options): void
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
