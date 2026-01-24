<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Handles console command events to inject and resolve global options.
 *
 * This listener intercepts console command execution events and uses the {@see GlobalOptionsProviderRegistry}
 * to add global options to commands and resolve their values from the input. It ensures that global
 * options are available to all console commands and properly bound to the command definition.
 */
class GlobalOptionsListener
{
    /**
     * @var GlobalOptionsProviderRegistry
     */
    private $globalOptionsRegistry;

    public function __construct(GlobalOptionsProviderRegistry $globalOptionsRegistry)
    {
        $this->globalOptionsRegistry = $globalOptionsRegistry;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        $this->globalOptionsRegistry->addGlobalOptions($command);
        $this->rebindDefinition($command, $input);
        $this->globalOptionsRegistry->resolveGlobalOptions($input);
    }

    private function rebindDefinition(Command $command, InputInterface $input)
    {
        /**
         * Added only for compatibility with Symfony below 2.8
         */
        $command->mergeApplicationDefinition();
        $input->bind($command->getDefinition());
    }
}
