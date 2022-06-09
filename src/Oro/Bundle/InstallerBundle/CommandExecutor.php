<?php

namespace Oro\Bundle\InstallerBundle;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Component\PhpUtils\Tools\CommandExecutor\AbstractCommandExecutor;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * The class that contains a set of methods to simplify execution of console commands.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CommandExecutor extends AbstractCommandExecutor
{
    private const DEFAULT_TIMEOUT = 300;

    private OutputInterface $output;
    private Application $application;
    private OroDataCacheManager $dataCacheManager;
    private ?int $lastCommandExitCode = null;
    private ?string $lastCommandLine = null;

    public function __construct(
        ?string $env,
        OutputInterface $output,
        Application $application,
        OroDataCacheManager $dataCacheManager = null
    ) {
        $this->env = $env;
        $this->output = $output;
        $this->application = $application;
        $this->dataCacheManager = $dataCacheManager;
        $this->defaultOptions = [
            'process-timeout' => self::DEFAULT_TIMEOUT
        ];
    }

    /**
     * Launches a command.
     * If '--process-isolation' parameter is specified the command will be launched as a separate process.
     * In this case you can parameter '--process-timeout' to set the process timeout
     * in seconds. Default timeout is 300 seconds.
     * The '--process-timeout' parameter can be used to set the process timeout
     * in seconds. Default timeout is 300 seconds.
     * If '--ignore-errors' parameter is specified any errors are ignored;
     * otherwise, an exception is raises if an error happened.
     *
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     */
    public function runCommand(string $command, array $params = []): self
    {
        $this->lastCommandLine = null;
        $this->lastCommandExitCode = null;

        // Array of parameters which will be passed to Process instance
        $params = $this->prepareParameters($command, $params);

        $ignoreErrors = false;
        if (\array_key_exists('--ignore-errors', $params)) {
            $ignoreErrors = true;
            unset($params['--ignore-errors']);
        }

        if (\array_key_exists('--process-isolation', $params)) {
            unset($params['--process-isolation']);
            $processArguments = [self::getPhpExecutable(), $_SERVER['argv'][0]];

            $processTimeout = null;
            if (\array_key_exists('--process-timeout', $params)) {
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

            $this->lastCommandLine = $process->getCommandLine();

            $output = $this->output;
            try {
                $process->run(
                    function ($type, $data) use ($output) {
                        $output->write($data);
                    }
                );
            } finally {
                $this->lastCommandExitCode = $process->getExitCode();
            }

            // synchronize all data caches
            $this->dataCacheManager?->sync();
        } else {
            if (\array_key_exists('--process-timeout', $params)) {
                unset($params['--process-timeout']);
            }

            $this->lastCommandLine = '';

            $this->application->setAutoExit(false);
            $originalVerbosity = $this->getVerbosity();
            try {
                $this->lastCommandExitCode = $this->application->run(new ArrayInput($params), $this->output);
            } finally {
                $this->setVerbosity($originalVerbosity);
                $this->application->setAutoExit(true);
            }
        }

        $this->processResult($ignoreErrors);

        return $this;
    }

    /**
     * Gets the default value of a given option.
     */
    public function getDefaultOption(string $name): mixed
    {
        return $this->defaultOptions[$name] ?? null;
    }

    /**
     * Sets the default value of a given option.
     */
    public function setDefaultOption(string $name, mixed $value = true): self
    {
        $this->defaultOptions[$name] = $value;

        return $this;
    }

    /**
     * Gets an exit code of last executed command.
     */
    public function getLastCommandExitCode(): int
    {
        return $this->lastCommandExitCode;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareParameters(string $command, array $params): array
    {
        $params = parent::prepareParameters($command, $params);

        if (!$this->hasVerbosityParameter($params)) {
            switch ($this->output->getVerbosity()) {
                case OutputInterface::VERBOSITY_DEBUG:
                    $params['-vvv'] = true;
                    break;
                case OutputInterface::VERBOSITY_VERY_VERBOSE:
                    $params['-vv'] = true;
                    break;
                case OutputInterface::VERBOSITY_VERBOSE:
                    $params['-v'] = true;
                    break;
                case OutputInterface::VERBOSITY_QUIET:
                    $params['-q'] = true;
                    break;
            }
        } elseif ($this->output->isQuiet()) {
            $toRemove = $this->getVerbosityParameters($params);
            if ($toRemove) {
                foreach ($toRemove as $name) {
                    unset($params[$name]);
                }
                $params['-q'] = true;
            }
        }

        return $params;
    }

    private function hasVerbosityParameter(array $params): bool
    {
        foreach ($params as $name => $value) {
            if ($this->isVerbosityParameter($name) || $this->isQuietVerbosityParameter($name)) {
                return true;
            }
        }

        return false;
    }

    private function isVerbosityParameter(string $name): bool
    {
        return '-v' === $name || '-vv' === $name || '-vvv' === $name || '--verbose' === $name;
    }

    private function isQuietVerbosityParameter(string $name): bool
    {
        return '-q' === $name || '--quiet' === $name;
    }

    private function getVerbosityParameters(array $params): array
    {
        $result = [];
        foreach ($params as $name => $value) {
            if ($this->isVerbosityParameter($name)) {
                $result[] = $name;
            }
        }

        return $result;
    }

    private function getVerbosity(): array
    {
        /**
         * @link https://github.com/symfony/symfony/pull/24425
         * @see  \Symfony\Component\Console\Application::configureIO
         */
        return [
            $this->output->getVerbosity(),
            (int)getenv('SHELL_VERBOSITY'),
            $_ENV['SHELL_VERBOSITY'] ?? null,
            $_SERVER['SHELL_VERBOSITY'] ?? null
        ];
    }

    /**
     * @param array $verbosity The data returned by {@see getVerbosity}
     */
    private function setVerbosity(array $verbosity): void
    {
        $this->output->setVerbosity($verbosity[0]);
        if (\function_exists('putenv')) {
            @putenv('SHELL_VERBOSITY=' . $verbosity[1]);
        }
        if (null === $verbosity[2]) {
            unset($_ENV['SHELL_VERBOSITY']);
        } else {
            $_ENV['SHELL_VERBOSITY'] = $verbosity[2];
        }
        if (null === $verbosity[3]) {
            unset($_SERVER['SHELL_VERBOSITY']);
        } else {
            $_SERVER['SHELL_VERBOSITY'] = $verbosity[3];
        }
    }

    private function processResult(bool $ignoreErrors): void
    {
        if (0 !== $this->lastCommandExitCode) {
            if ($ignoreErrors) {
                $this->output->writeln(sprintf(
                    '<error>The command terminated with an exit code: %u.</error>',
                    $this->lastCommandExitCode
                ));
            } else {
                throw new \RuntimeException(sprintf(
                    'The command %s terminated with an exit code: %u.',
                    $this->lastCommandLine,
                    $this->lastCommandExitCode
                ));
            }
        }
    }

    /**
     * Checks whether specified command is running now.
     *
     * @param string $command  The command name or prefix
     * @param bool   $isPrefix Determines whether $command is a command name or prefix
     *
     * @return bool
     */
    public static function isCommandRunning(string $command, bool $isPrefix = false): bool
    {
        if (self::isCurrentCommand($command, $isPrefix)) {
            return true;
        }

        if (\defined('PHP_WINDOWS_VERSION_BUILD')) {
            $cmd = 'WMIC path win32_process get Processid,Commandline | findstr "%s" | findstr /V findstr';
        } else {
            $cmd = sprintf('ps ax | grep "%s" | grep -v grep', $command);
        }

        $process = Process::fromShellCommandline($cmd);
        $process->run();
        $results = $process->getOutput();

        return !empty($results);
    }

    /**
     * Checks if this process executes specified command.
     *
     * @param string $command  The command name or prefix
     * @param bool   $isPrefix Determines whether $command is a command name or prefix
     *
     * @return bool
     */
    public static function isCurrentCommand(string $command, bool $isPrefix = false): bool
    {
        if (isset($_SERVER['argv']) && php_sapi_name() === 'cli') {
            if (!$isPrefix) {
                return \in_array($command, $_SERVER['argv'], true);
            }

            foreach ($_SERVER['argv'] as $arg) {
                if (\is_string($arg) && str_starts_with($arg, $command)) {
                    return true;
                }
            }
        }

        return false;
    }
}
