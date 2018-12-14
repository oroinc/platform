<?php

namespace Oro\Component\PhpUtils\Tools\CommandExecutor;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * The class that contains a set of methods to simplify execution of console commands in a separate process.
 */
class CommandExecutor implements CommandExecutorInterface
{
    public const DEFAULT_TIMEOUT = 300;

    /** @var string */
    protected $consoleCmdPath;

    /** @var string */
    protected $env;

    /** @var array */
    protected $defaultOptions = ['process-timeout' => self::DEFAULT_TIMEOUT];

    /**
     * @param string $consoleCmdPath
     * @param string $env
     */
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

        $pb = new ProcessBuilder();
        $pb
            ->add($this->getPhp())
            ->add($this->consoleCmdPath);

        if (array_key_exists('--process-timeout', $params)) {
            $pb->setTimeout($params['--process-timeout']);
            unset($params['--process-timeout']);
        }

        foreach ($params as $name => $val) {
            $this->processParameter($pb, $name, $val);
        }

        $process = $pb
            ->inheritEnvironmentVariables(true)
            ->getProcess();

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

    /**
     * @param string $command
     * @param array $params
     *
     * @return array
     */
    protected function prepareParameters($command, array $params): array
    {
        $params = array_merge(
            [
                'command' => $command
            ],
            $params
        );

        if ($this->env && $this->env !== 'dev') {
            $params['--env'] = $this->env;
        }

        foreach ($this->defaultOptions as $name => $value) {
            $paramName = '--' . $name;
            if (!array_key_exists($paramName, $params)) {
                $params[$paramName] = $value;
            }
        }

        return $params;
    }

    /**
     * @param ProcessBuilder $pb
     * @param string $name
     * @param array|string|null $value
     */
    protected function processParameter(ProcessBuilder $pb, $name, $value): void
    {
        if ($name && '-' === $name[0]) {
            if ($value === true) {
                $this->addParameter($pb, $name);
            } elseif ($value !== false) {
                $this->addParameter($pb, $name, $value);
            }
        } else {
            $this->addParameter($pb, $value);
        }
    }

    /**
     * @param ProcessBuilder $pb
     * @param string $name
     * @param array|string|null $value
     */
    protected function addParameter(ProcessBuilder $pb, $name, $value = null): void
    {
        $parameters = [];

        if (null !== $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parameters[] = sprintf('%s=%s', $name, $item);
                }
            } else {
                $parameters[] = sprintf('%s=%s', $name, $value);
            }
        } else {
            $parameters[] = $name;
        }

        foreach ($parameters as $parameter) {
            $pb->add($parameter);
        }
    }

    /**
     * Finds the PHP executable.
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function getPhp(): string
    {
        $phpFinder = new PhpExecutableFinder();
        $phpPath = $phpFinder->find();
        if (!$phpPath) {
            throw new FileNotFoundException('The PHP executable could not be found.');
        }

        return $phpPath;
    }
}
