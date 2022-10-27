<?php

namespace Oro\Bundle\MaintenanceBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is thrown each time a system locks/unlock for a maintenance mode.
 */
final class MaintenanceEvent extends Event
{
    /**
     * The maintenance.on event is thrown each time a system locks for a maintenance mode.
     *
     * @var string
     */
    public const MAINTENANCE_ON = 'maintenance.on';

    /**
     * The maintenance.off event is thrown each time a system unlocks from a maintenance mode.
     *
     * @var string
     */
    public const MAINTENANCE_OFF = 'maintenance.off';
}
