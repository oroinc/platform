<?php

namespace Oro\Bundle\MaintenanceBundle\Maintenance;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Event\MaintenanceEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class represents Maintenance Mode
 */
class Mode
{
    private DriverFactory $factory;

    private EventDispatcherInterface $dispatcher;

    public function __construct(DriverFactory $factory, EventDispatcherInterface $dispatcher)
    {
        $this->factory    = $factory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @see \Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver::createLock()
     */
    public function on(): bool
    {
        $result = $this->factory->getDriver()->lock();

        if ($result) {
            $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_ON);
        }

        return $result;
    }

    /**
     * @see \Oro\Bundle\MaintenanceBundle\Drivers\AbstractDriver::createUnlock()
     */
    public function off(): bool
    {
        $result = $this->factory->getDriver()->unlock();

        if ($result) {
            $this->dispatcher->dispatch(new MaintenanceEvent(), MaintenanceEvent::MAINTENANCE_OFF);
        }

        return $result;
    }

    /**
     * Turn on maintenance mode and register shutdown function to turn it off
     */
    public function activate(): void
    {
        $this->on();

        register_shutdown_function(
            function () {
                $this->off();
            }
        );
    }
}
