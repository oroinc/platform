<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

/**
 * The factory to create driver for Maintenance Mode.
 */
class DriverFactory
{
    protected array $driverOptions;

    private ?AbstractDriver $driver = null;

    public function __construct(array $driverOptions)
    {
        $this->driverOptions = $driverOptions;
    }

    public function getDriver(): AbstractDriver
    {
        if (null === $this->driver) {
            $this->driver = new FileDriver($this->driverOptions['options']);
        }

        return $this->driver;
    }
}
