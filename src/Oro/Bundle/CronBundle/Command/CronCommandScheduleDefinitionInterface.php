<?php

namespace Oro\Bundle\CronBundle\Command;

/**
 * Represents a CRON command schedule definition.
 * This interface must be implemented by all commands that are executed by the CRON.
 */
interface CronCommandScheduleDefinitionInterface
{
    /**
     * Defines a default schedule definition for a CRON command.
     * Example: "5 * * * *"
     * @see \Oro\Bundle\CronBundle\Entity\Schedule::setDefinition()
     *
     * @return string
     */
    public function getDefaultDefinition();
}
