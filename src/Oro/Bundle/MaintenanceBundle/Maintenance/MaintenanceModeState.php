<?php

namespace Oro\Bundle\MaintenanceBundle\Maintenance;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;

/**
 * Class represents Maintenance Mode State.
 */
class MaintenanceModeState
{
    public function __construct(protected DriverFactory $factory)
    {
    }

    /**
     * Whether maintenance mode is on or not
     */
    public function isOn(): bool
    {
        return $this->factory->getDriver()->decide();
    }
}
