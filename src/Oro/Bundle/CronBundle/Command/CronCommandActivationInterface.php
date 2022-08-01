<?php

namespace Oro\Bundle\CronBundle\Command;

/**
 * Represents a CRON command with conditional activation.
 */
interface CronCommandActivationInterface
{
    /**
     * Checks whether the CRON command is active (i.e. properly configured, etc).
     *
     * @return bool
     */
    public function isActive();
}
