<?php

namespace Oro\Bundle\CronBundle\Command;

interface CronCommandInterface extends ActiveCronCommandInterface
{
    /**
     * Define default cron schedule definition for a command.
     * Example: "5 * * * *"
     *
     * @see    \Oro\Bundle\CronBundle\Entity\Schedule::setDefinition()
     * @return string
     */
    public function getDefaultDefinition();
}
