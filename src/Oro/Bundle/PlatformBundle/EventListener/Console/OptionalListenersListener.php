<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
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
        $input = $event->getInput();

        $listeners = $this->getListenersToDisable($input);
        if (!empty($listeners)) {
            $this->listenersManager->disableListeners($listeners);
        }
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
