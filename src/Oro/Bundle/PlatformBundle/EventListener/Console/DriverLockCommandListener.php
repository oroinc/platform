<?php

namespace Oro\Bundle\PlatformBundle\EventListener\Console;

use Oro\Bundle\PlatformBundle\Maintenance\MaintenanceEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatch MaintenanceEvent after executing lexik:maintenance:lock/unlock commands
 */
class DriverLockCommandListener
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
        switch ($event->getCommand()->getName()) {
            case DriverLockCommandListener::LEXIK_MAINTENANCE_LOCK:
                $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_ON);
                break;
            case DriverLockCommandListener::LEXIK_MAINTENANCE_UNLOCK:
                $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_OFF);
                break;
        }
    }
}
