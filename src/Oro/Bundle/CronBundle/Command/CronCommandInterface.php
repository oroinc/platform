<?php

namespace Oro\Bundle\CronBundle\Command;

interface CronCommandInterface
{
    /**
     * Define default cron schedule definition for a command.
     * Example: "5 * * * *"
     *
     * @see    \Oro\Bundle\CronBundle\Entity\Schedule::setDefinition()
     * @return string
     */
    public function getDefaultDefinition();

    /**
     * @deprecated Since 2.0.3. Will be removed in 2.1. Must be refactored at BAP-13973
     *
     * Checks if the command active (i.e. properly configured etc).
     *
     * @return bool
     */
    public function isActive();
}
