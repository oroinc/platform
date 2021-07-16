<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

/**
 * Interface that every driver should implements in case it works with ttl (time to live) option
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
interface DriverTtlInterface
{
    /**
     * Set time to live for overwrite basic configuration
     *
     * @param int $value ttl value
     */
    public function setTtl(int $value): void;

    /**
     * Return time to live
     *
     * @return integer
     */
    public function getTtl(): int;

    /**
     * Has ttl
     *
     * @return bool
     */
    public function hasTtl(): bool;
}
