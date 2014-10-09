<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use Oro\Bundle\PlatformBundle\Command\OptionalListenersCommand;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class OptionalListenersListener
{
    const ALL_OPTIONAL_LISTENERS_VALUE = 'all';
    const DISABLE_OPTIONAL_LISTENERS   = 'disabled_listeners';

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
                self::DISABLE_OPTIONAL_LISTENERS,
                null,
                InputOption::VALUE_OPTIONAL|InputOption::VALUE_IS_ARRAY,
                sprintf(
                    'Disable optional listeners. To disable all listeners, use value "%s". '
                    .'Use "%s" command to see list of available optional listeners',
                    self::ALL_OPTIONAL_LISTENERS_VALUE,
                    OptionalListenersCommand::NAME
                )
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
        $listenerList = $input->getOption(self::DISABLE_OPTIONAL_LISTENERS);
        if (!empty($listenerList)) {
            if (count($listenerList) === 1 && $listenerList[0] == self::ALL_OPTIONAL_LISTENERS_VALUE) {
                $listeners = [];
            } else {
                $listeners = $listenerList;
            }
        }

        return $listeners;
    }
}
