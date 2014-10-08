<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class OptionalListenersListener
{
    const DISABLE_ALL_OPTIONAL_LISTENERS = 'disable_all_listeners';
    const DISABLE_OPTIONAL_LISTENERS = 'disable_listener';

    /**
     * @var OptionalListenerManager
     */
    protected $listenersManager;

    /**
     * @param OptionalListenerManager $listenerManager
     */
    public function __construct(OptionalListenerManager $listenerManager)
    {
        $this->listenersManager = $listenerManager;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();

        $this->addOptionsToCommand($command);
        $listeners = $this->getListenersToDisable($input, $command);
        if (!is_null($listeners)) {
            $this->listenersManager->disableListeners($listeners);
        }
    }

    /**
     * @param Command $command
     */
    protected function addOptionsToCommand(Command $command)
    {
        $inputDefinition = $command->getApplication()->getDefinition();
        $inputDefinition->addOption(
            new InputOption(
                self::DISABLE_ALL_OPTIONAL_LISTENERS,
                null,
                InputOption::VALUE_NONE,
                'Disable all optional listeners'
            )
        );
        $inputDefinition->addOption(
            new InputOption(
                self::DISABLE_OPTIONAL_LISTENERS,
                null,
                InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY,
                'Disable given optional listeners'
            )
        );

        $command->mergeApplicationDefinition();
    }

    /**
     * @param InputInterface $input
     *
     * @return array|null
     */
    protected function getListenersToDisable(InputInterface $input, Command $command)
    {
        $listeners = null;
        $input->bind($command->getDefinition());
        if ($input->getOption(self::DISABLE_ALL_OPTIONAL_LISTENERS)) {
            $listeners = [];
        }
        $listenerList = $input->getOption(self::DISABLE_OPTIONAL_LISTENERS);
        if (!empty($listenerList)) {
            $listeners = $listenerList;
        }

        return $listeners;
    }
}
