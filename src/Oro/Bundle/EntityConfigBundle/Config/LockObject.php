<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

/**
 * This class allows to temporary disable some functionality.
 * For example it is used to disable loading of entity configs during entity config cache warming up.
 */
class LockObject
{
    /** @var int */
    private $counter = 0;

    /**
     * Indicates whether this object is in locked state.
     *
     * @return bool
     */
    public function isLocked()
    {
        return $this->counter > 0;
    }

    /**
     * Switches this object in the locked state.
     * To remove the lock use the method {@see unlock()}.
     */
    public function lock()
    {
        $this->counter++;
    }

    /**
     * Removes a lock previously created by the method {@see lock()}.
     */
    public function unlock()
    {
        if ($this->counter === 0) {
            throw new \RuntimeException('Cannot remove a lock from already unlocked object.');
        }
        $this->counter--;
    }
}
