<?php

namespace Oro\Bundle\CronBundle\Engine;

/**
 * Defines the contract for executing console commands in the cron system.
 *
 * Implementations of this interface provide different strategies for running commands:
 * - Asynchronously via message queue (for standard cron commands)
 * - Synchronously in isolated processes (for commands requiring immediate execution)
 * - With output capture for logging and debugging purposes
 *
 * This abstraction allows the cron system to be flexible in how commands are executed,
 * supporting both background processing and direct execution patterns.
 */
interface CommandRunnerInterface
{
    /**
     * @param string $commandName
     * @param array $commandArguments
     */
    public function run($commandName, $commandArguments = []);
}
