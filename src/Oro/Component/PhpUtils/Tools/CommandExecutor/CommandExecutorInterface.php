<?php

namespace Oro\Component\PhpUtils\Tools\CommandExecutor;

use Psr\Log\LoggerInterface;

/**
 * Specifies the structure that is required to execute console commands in a separate process.
 */
interface CommandExecutorInterface
{
    /**
     * Launches a command as a separate process.
     *
     * @param string $command
     * @param array $params
     * @param LoggerInterface|null $logger
     *
     * @return int The exit status code
     */
    public function runCommand(string $command, array $params = [], LoggerInterface $logger = null): int;

    /**
     * Sets the default value of a given option
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setDefaultOption(string $name, $value = true): CommandExecutorInterface;

    /**
     * Gets the default value of a given option
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getDefaultOption(string $name);
}
