<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

use Oro\Bundle\PlatformBundle\Command\OptionalListenersCommand;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;

class OptionalListenersListener
{
    const ALL_OPTIONAL_LISTENERS_VALUE = 'all';
    const DISABLE_OPTIONAL_LISTENERS   = 'disabled-listeners';

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

        $this->addOptionsToCommand($command, $input);
        $listeners = $this->getListenersToDisable($input);
        if (!empty($listeners)) {
            $this->listenersManager->disableListeners($listeners);
        }
    }

    /**
     * @param Command $command
     * @param InputInterface $input
     */
    protected function addOptionsToCommand(Command $command, InputInterface $input)
    {
        $inputDefinition = $command->getApplication()->getDefinition();
        $disableOptionalListenerOption = new InputOption(
            self::DISABLE_OPTIONAL_LISTENERS,
            null,
            InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY,
            sprintf(
                'Disable optional listeners, "%s" to disable all listeners, '
                .'command "%s" shows all listeners',
                self::ALL_OPTIONAL_LISTENERS_VALUE,
                OptionalListenersCommand::NAME
            )
        );
        /**
         * Starting from Symfony 2.8 event 'ConsoleCommandEvent' fires after all definitions were merged.
         */
        $inputDefinition->addOption($disableOptionalListenerOption);
        $command->getDefinition()->addOption($disableOptionalListenerOption);
        /**
         * Added only for compatibility with Symfony below 2.8
         */
        $command->mergeApplicationDefinition();
        $input->bind($command->getDefinition());
    }

    /**
     *
     * @param InputInterface $input
     * @return array
     */
    protected function getListenersToDisable(InputInterface $input)
    {
        $listeners = [];

        $listenerList = $input->getOption(self::DISABLE_OPTIONAL_LISTENERS);
        if (!empty($listenerList)) {
            if (count($listenerList) === 1 && $listenerList[0] == self::ALL_OPTIONAL_LISTENERS_VALUE) {
                $listeners = $this->listenersManager->getListeners();
            } else {
                $listeners = $listenerList;
            }
        }

        return $listeners;
    }
}
