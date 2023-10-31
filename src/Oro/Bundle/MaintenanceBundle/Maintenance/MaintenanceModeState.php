<?php

namespace Oro\Bundle\MaintenanceBundle\Maintenance;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;

/**
 * Represents the maintenance mode state.
 */
class MaintenanceModeState
{
    private DriverFactory $factory;

    public function __construct(DriverFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Checks whether the maintenance mode is on or not.
     */
    public function isOn(): bool
    {
        return $this->factory->getDriver()->decide();
    }
}
