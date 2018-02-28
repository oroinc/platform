<?php

namespace Oro\Bundle\PlatformBundle\Maintenance;

use Lexik\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Mode
{
    /**
     * @var DriverFactory
     */
    protected $factory;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param DriverFactory            $factory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(DriverFactory $factory, EventDispatcherInterface $dispatcher)
    {
        $this->factory    = $factory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\MaintenanceBundle\Drivers.AbstractDriver::createLock()
     */
    public function on()
    {
        $result = $this->factory->getDriver()->lock();

        if ($result) {
            $this->dispatcher->dispatch(Events::MAINTENANCE_ON);
        }

        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see Lexik\Bundle\MaintenanceBundle\Drivers.AbstractDriver::createUnlock()
     */
    public function off()
    {
        $result = $this->factory->getDriver()->unlock();

        if ($result) {
            $this->dispatcher->dispatch(Events::MAINTENANCE_OFF);
        }

        return $result;
    }

    /**
     * Whether maintenance mode is on or not
     *
     * @return bool
     */
    public function isOn()
    {
        return $this->factory->getDriver()->decide();
    }

    /**
     * Turn on maintenance mode and register shutdown function to turn it off
     */
    public function activate()
    {
        $this->on();

        register_shutdown_function(
            function () {
                $this->off();
            }
        );
    }
}
