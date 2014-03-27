<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Oro\Bundle\PlatformBundle\Maintenance\Events;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CommandListener
{
    const LEXIK_MAINTENANCE_LOCK = 'lexik:maintenance:lock';
    const LEXIK_MAINTENANCE_UNLOCK = 'lexik:maintenance:unlock';

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function afterExecute(ConsoleTerminateEvent $event)
    {
        switch ($event->getCommand()
            ->getName()) {
            case CommandListener::LEXIK_MAINTENANCE_LOCK:
                $this->dispatcher->dispatch(Events::MAINTENANCE_ON);
                break;
            case CommandListener::LEXIK_MAINTENANCE_UNLOCK:
                $this->dispatcher->dispatch(Events::MAINTENANCE_OFF);
                break;
        }
    }
}
