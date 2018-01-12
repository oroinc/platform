<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;

class GlobalOptionsListener
{
    /**
     * @var GlobalOptionsProviderRegistry
     */
    private $globalOptionsRegistry;

    /**
     * @param GlobalOptionsProviderRegistry $globalOptionsRegistry
     */
    public function __construct(GlobalOptionsProviderRegistry $globalOptionsRegistry)
    {
        $this->globalOptionsRegistry = $globalOptionsRegistry;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        $this->globalOptionsRegistry->addGlobalOptions($command);
        $this->rebindDefinition($command, $input);
        $this->globalOptionsRegistry->resolveGlobalOptions($input);
    }

    /**
     * @param Command $command
     * @param InputInterface $input
     */
    private function rebindDefinition(Command $command, InputInterface $input)
    {
        /**
         * Added only for compatibility with Symfony below 2.8
         */
        $command->mergeApplicationDefinition();
        $input->bind($command->getDefinition());
    }
}
