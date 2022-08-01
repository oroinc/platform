<?php

namespace Oro\Bundle\CronBundle\Command;

/**
 * Represents a service that is used by the "oro:cron" ({@see \Oro\Bundle\CronBundle\Command\CronCommand}) command
 * and {@see \Oro\Bundle\CronBundle\EventListener\CronCommandListener} to check whether a specific CRON command
 * is enabled as a feature of an application.
 * It is intended that classes that implement this interface will implement the following logic:
 * * a common logic for all CRON commands
 * * implement a feature related logic for specific CRON commands
 * * discard a common logic for some CRON commands if needed
 * * extends logic of the {@see \Oro\Bundle\CronBundle\Command\CronCommandActivationInterface::isActive} method
 *   without a need to extend a CRON command
 */
interface CronCommandFeatureCheckerInterface
{
    /**
     * Checks whether the given CRON command is allowed to be executed.
     */
    public function isFeatureEnabled(string $commandName): bool;
}
