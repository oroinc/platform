<?php

namespace Oro\Bundle\CronBundle\Command;

/**
 * Represents a CRON command that requires specific default arguments in its schedule.
 */
interface CronCommandScheduleArgumentsInterface
{
    /**
     * Defines the default arguments for a CRON command's schedule.
     * Example: ["--process-timeout=1200"]
     * @see \Oro\Bundle\CronBundle\Entity\Schedule::setArguments()
     *
     * These arguments are resolved from "--name=value" strings into keyed options only on the
     * asynchronous (message queue) execution path, by CronCommand::resolveOptions(). A command that
     * also implements SynchronousCommandInterface bypasses that resolution and would receive the raw
     * "--name=value" string as a literal, undeclared CLI argument - do not combine the two interfaces
     * unless the synchronous path is updated to resolve arguments the same way.
     *
     * @return string[]
     */
    public function getDefaultArguments(): array;
}
