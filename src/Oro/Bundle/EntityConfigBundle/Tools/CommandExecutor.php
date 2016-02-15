<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Oro\Bundle\InstallerBundle\Process\PhpExecutableFinder;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;

class CommandExecutor
{
    const DEFAULT_TIMEOUT = 300;

    /**
     * @var string
     */
    protected $consoleCmdPath;

    /**
     * @var string
     */
    protected $env;

    /**
     * @var OroDataCacheManager
     */
    protected $dataCacheManager;

    /** @var array */
    protected $defaultOptions;

    /**
     * @var int
     *
     * @deprecated since 1.8. Use {@see getDefaultOption('process-timeout')} instead
     */
    protected $defaultTimeout = self::DEFAULT_TIMEOUT;

    /**
     * Constructor
     *
     * @param string              $consoleCmdPath
     * @param string              $env
     * @param OroDataCacheManager $dataCacheManager
     */
    public function __construct(
        $consoleCmdPath,
        $env,
        OroDataCacheManager $dataCacheManager = null
    ) {
        $this->consoleCmdPath   = $consoleCmdPath;
        $this->env              = $env;
        $this->dataCacheManager = $dataCacheManager;
        $this->defaultOptions   = [
            'process-timeout' => self::DEFAULT_TIMEOUT
        ];
    }

    /**
     * Launches a command as a separate process.
     *
     * The '--process-timeout' parameter can be used to set the process timeout
     * in seconds. Default timeout is 300 seconds.
     * If '--ignore-errors' parameter is specified any errors are ignored;
     * otherwise, an exception is raises if an error happened.
     * If '--disable-cache-sync' parameter is specified a synchronization of caches between current
     * process and its child processes are disabled.
     *
     * @param string               $command
     * @param array                $params
     * @param LoggerInterface|null $logger
     *
     * @return integer The exit status code
     *
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     */
    public function runCommand($command, $params = [], LoggerInterface $logger = null)
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

        $disableCacheSync = false;
        if (array_key_exists('--disable-cache-sync', $params)) {
            $disableCacheSync = $params['--disable-cache-sync'];
            unset($params['--disable-cache-sync']);
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

        // synchronize all data caches
        if ($this->dataCacheManager && !$disableCacheSync) {
            $this->dataCacheManager->sync();
        }

        $this->processResult($exitCode, $ignoreErrors, $logger);

        return $exitCode;
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
     * @param int             $exitCode
     * @param bool            $ignoreErrors
     * @param LoggerInterface $logger
     * @throws \RuntimeException
     */
    protected function processResult($exitCode, $ignoreErrors, LoggerInterface $logger)
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
        $parameters = array();

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
        $phpFinder = new PhpExecutableFinder();
        $phpPath   = $phpFinder->find();
        if (!$phpPath) {
            throw new FileNotFoundException('The PHP executable could not be found.');
        }

        return $phpPath;
    }

    /**
     * @return int
     *
     * @deprecated since 1.8. Use {@see getDefaultOption('process-timeout')} instead
     */
    public function getDefaultTimeout()
    {
        return $this->getDefaultOption('process-timeout');
    }

    /**
     * @param int $defaultTimeout
     *
     * @deprecated since 1.8. Use {@see setDefaultOption('process-timeout', $timeout)} instead
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        $this->setDefaultOption('process-timeout', $defaultTimeout);
    }
}
