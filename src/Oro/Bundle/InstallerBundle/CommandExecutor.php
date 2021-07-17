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
 */
class CommandExecutor extends AbstractCommandExecutor
{
    const DEFAULT_TIMEOUT = 300;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var OroDataCacheManager
     */
    protected $dataCacheManager;

    /**
     * @var int
     */
    protected $lastCommandExitCode;

    /**
     * @var string
     */
    protected $lastCommandLine;

    /**
     * Constructor
     *
     * @param string|null         $env
     * @param OutputInterface     $output
     * @param Application         $application
     * @param OroDataCacheManager $dataCacheManager
     */
    public function __construct(
        $env,
        OutputInterface $output,
        Application $application,
        OroDataCacheManager $dataCacheManager = null
    ) {
        $this->env              = $env;
        $this->output           = $output;
        $this->application      = $application;
        $this->dataCacheManager = $dataCacheManager;
        $this->defaultOptions   = [
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
     * @param string $command
     * @param array  $params
     * @return CommandExecutor
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     */
    public function runCommand($command, $params = [])
    {
        $this->lastCommandLine = null;
        $this->lastCommandExitCode = null;

        // Array of parameters which will be passed to Process instance
        $params = $this->prepareParameters($command, $params);

        $ignoreErrors = false;
        if (array_key_exists('--ignore-errors', $params)) {
            $ignoreErrors = true;
            unset($params['--ignore-errors']);
        }

        if (array_key_exists('--process-isolation', $params)) {
            unset($params['--process-isolation']);
            $processArguments = [self::getPhpExecutable(), $_SERVER['argv'][0]];

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
            $process->inheritEnvironmentVariables(true);

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
            if ($this->dataCacheManager) {
                $this->dataCacheManager->sync();
            }
        } else {
            if (array_key_exists('--process-timeout', $params)) {
                unset($params['--process-timeout']);
            }

            $this->lastCommandLine = '';

            $this->application->setAutoExit(false);
            $originalVerbosity = $this->output->getVerbosity();
            try {
                $this->lastCommandExitCode = $this->application->run(new ArrayInput($params), $this->output);
            } finally {
                $this->output->setVerbosity($originalVerbosity);
                $this->application->setAutoExit(true);
            }
        }

        $this->processResult($ignoreErrors);

        return $this;
    }

    /**
     * Gets the default value of a given option
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getDefaultOption($name)
    {
        return isset($this->defaultOptions[$name]) ? $this->defaultOptions[$name] : null;
    }

    /**
     * Sets the default value of a given option
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return self
     */
    public function setDefaultOption($name, $value = true)
    {
        $this->defaultOptions[$name] = $value;

        return $this;
    }

    /**
     * Gets an exit code of last executed command
     *
     * @return int
     */
    public function getLastCommandExitCode()
    {
        return $this->lastCommandExitCode;
    }

    /**
     * {@inheritDoc}
     */
    protected function prepareParameters($command, array $params): array
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
        }

        return $params;
    }

    private function hasVerbosityParameter(array $params): bool
    {
        foreach ($params as $name => $value) {
            if ('-v' === $name
                || '-vv' === $name
                || '-vvv' === $name
                || '--verbose' === $name
                || '-q' === $name
                || '--quiet' === $name
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $ignoreErrors
     * @throws \RuntimeException
     */
    protected function processResult($ignoreErrors)
    {
        if (0 !== $this->lastCommandExitCode) {
            if ($ignoreErrors) {
                $this->output->writeln(
                    sprintf(
                        '<error>The command terminated with an exit code: %u.</error>',
                        $this->lastCommandExitCode
                    )
                );
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
     * Check whether specified command is running now
     *
     * @param string $command  The command name or prefix
     * @param bool   $isPrefix Determines whether $command is a command name or prefix
     *
     * @return bool
     */
    public static function isCommandRunning($command, $isPrefix = false)
    {
        if (self::isCurrentCommand($command, $isPrefix)) {
            return true;
        }

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $cmd = 'WMIC path win32_process get Processid,Commandline | findstr "%s" | findstr /V findstr';
        } else {
            $cmd = sprintf('ps ax | grep "%s" | grep -v grep', $command);
        }

        $process = new Process($cmd);
        $process->run();
        $results = $process->getOutput();

        return !empty($results);
    }

    /**
     * Check if this process executes specified command
     *
     * @param string $command  The command name or prefix
     * @param bool   $isPrefix Determines whether $command is a command name or prefix
     *
     * @return bool
     */
    public static function isCurrentCommand($command, $isPrefix = false)
    {
        if (isset($_SERVER['argv']) && php_sapi_name() === 'cli') {
            if (!$isPrefix) {
                return in_array($command, $_SERVER['argv'], true);
            }

            foreach ($_SERVER['argv'] as $arg) {
                if (is_string($arg) && strpos($arg, $command) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}
