<?php

namespace Oro\Component\PhpUtils\Tools\CommandExecutor;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;

/**
 * The class that contains a set of methods to simplify execution of console commands in a separate process.
 */
class CommandExecutor extends AbstractCommandExecutor implements CommandExecutorInterface
{
    public const DEFAULT_TIMEOUT = 300;

    /** @var string */
    protected $consoleCmdPath;

    /** @var array */
    protected $defaultOptions = ['process-timeout' => self::DEFAULT_TIMEOUT];

    public function __construct(string $consoleCmdPath, string $env)
    {
        $this->consoleCmdPath = $consoleCmdPath;
        $this->env = $env;
    }

    /**
     * Launches a command as a separate process.
     *
     * The '--process-timeout' parameter can be used to set the process timeout
     * in seconds. Default timeout is 300 seconds.
     * If '--ignore-errors' parameter is specified any errors are ignored;
     * otherwise, an exception is raises if an error happened.
     * process and its child processes are disabled.
     *
     * @param string $command
     * @param array $params
     * @param LoggerInterface|null $logger
     *
     * @return int The exit status code
     *
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     */
    public function runCommand(string $command, array $params = [], LoggerInterface $logger = null): int
    {
        $params = $this->prepareParameters($command, $params);

        $ignoreErrors = false;
        if (array_key_exists('--ignore-errors', $params)) {
            $ignoreErrors = true;
            unset($params['--ignore-errors']);
        }

        $processArguments = [self::getPhpExecutable(), $this->consoleCmdPath];

        $processTimeout = null;
        if (array_key_exists('--process-timeout', $params)) {
            $processTimeout = $params['--process-timeout'];
            //Timeout will be passed via method so is not needed in params anymore
            unset($params['--process-timeout']);
        }

        foreach ($params as $name => $val) {
            $this->processParameter($processArguments, $name, $val);
        }

        $process = new Process($processArguments);

        if ($processTimeout !== null) {
            $process->setTimeout($processTimeout);
        }

        if (!$logger) {
            $logger = new NullLogger();
        }

        $exitCode = $process->run(
            function ($type, $data) use ($logger) {
                if ($type === Process::ERR) {
                    $logger->error($data);
                } else {
                    $logger->info($data);
                }
            }
        );

        $this->processResult($exitCode, $ignoreErrors, $logger);

        return $exitCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption(string $name)
    {
        return $this->defaultOptions[$name] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOption(string $name, $value = true): CommandExecutorInterface
    {
        $this->defaultOptions[$name] = $value;

        return $this;
    }

    /**
     * @param int $exitCode
     * @param bool $ignoreErrors
     * @param LoggerInterface $logger
     * @throws \RuntimeException
     */
    protected function processResult($exitCode, $ignoreErrors, LoggerInterface $logger): void
    {
        if (0 !== $exitCode) {
            if ($ignoreErrors) {
                $logger->warning(sprintf('The command terminated with an exit code: %u.', $exitCode));
            } else {
                throw new \RuntimeException(sprintf('The command terminated with an exit code: %u.', $exitCode));
            }
        }
    }
}
