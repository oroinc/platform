<?php

namespace Oro\Bundle\InstallerBundle;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * The class that contains a set of methods to simplify execution of console commands.
 */
class CommandExecutor
{
    const DEFAULT_TIMEOUT = 300;

    /**
     * @var string|null
     */
    protected $env;

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

    /** @var array */
    protected $defaultOptions;

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

        $params = $this->prepareParameters($command, $params);

        $ignoreErrors = false;
        if (array_key_exists('--ignore-errors', $params)) {
            $ignoreErrors = true;
            unset($params['--ignore-errors']);
        }

        if (array_key_exists('--process-isolation', $params)) {
            unset($params['--process-isolation']);
            $pb = new ProcessBuilder();
            $pb
                ->add($this->getPhp())
                ->add($_SERVER['argv'][0]);

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
            try {
                $this->lastCommandExitCode = $this->application->run(new ArrayInput($params), $this->output);
            } finally {
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
     * @param string $command
     * @param array  $params
     *
     * @return array
     */
    protected function prepareParameters($command, array $params)
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
     * @param ProcessBuilder    $pb
     * @param string            $name
     * @param array|string|null $value
     */
    protected function processParameter(ProcessBuilder $pb, $name, $value)
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
     * @param ProcessBuilder    $pb
     * @param string            $name
     * @param array|string|null $value
     */
    protected function addParameter(ProcessBuilder $pb, $name, $value = null)
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
    protected function getPhp()
    {
        return self::getPhpExecutable();
    }

    /**
     * Finds the PHP executable.
     *
     * @return string
     * @throws FileNotFoundException
     */
    public static function getPhpExecutable()
    {
        $phpFinder = new PhpExecutableFinder();
        $phpPath   = $phpFinder->find();
        if (!$phpPath) {
            throw new FileNotFoundException('The PHP executable could not be found.');
        }

        return $phpPath;
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
