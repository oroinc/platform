<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

/**
 * Abstract class for drivers
 *
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
abstract class AbstractDriver
{
    protected array $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Test if lock exists
     */
    abstract public function isExists(): bool;

    /**
     * Result of locking
     */
    abstract protected function createLock(): bool;

    /**
     * Result of unlocking
     */
    abstract protected function createUnlock(): bool;

    public function lock(): bool
    {
        if (!$this->isExists()) {
            return $this->createLock();
        }

        return false;
    }

    public function unlock(): bool
    {
        if ($this->isExists()) {
            return $this->createUnlock();
        }

        return false;
    }

    /**
     * Checks if the maintenance mode is on or off.
     */
    public function decide(): bool
    {
        return $this->isExists();
    }

    /**
     * Options of driver
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
