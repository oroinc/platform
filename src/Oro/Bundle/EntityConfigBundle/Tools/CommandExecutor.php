<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

use Oro\Bundle\CacheBundle\Manager\OroDataCacheManager;
use Oro\Bundle\InstallerBundle\Process\PhpExecutableFinder;

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

    /**
     * @var int
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
    }

    /**
     * Launches a command as a separate process.
     *
     * The '--process-timeout' parameter can be used to set the process timeout
     * in seconds. Default timeout is 300 seconds.
     * If '--ignore-errors' parameter is specified any errors are ignored;
     * otherwise, an exception is raises if an error happened.
     *
     * @param string               $command
     * @param array                $params
     * @param LoggerInterface|null $logger
     *
     * @return integer The exit status code
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     */
    public function runCommand($command, $params = [], LoggerInterface $logger = null)
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
        } else {
            $pb->setTimeout($this->defaultTimeout);
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
                    $logger->notice($data);
                }
            }
        );

        // synchronize all data caches
        if ($this->dataCacheManager) {
            $this->dataCacheManager->sync();
        }

        $this->processResult($exitCode, $ignoreErrors, $logger);

        return $exitCode;
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
     * @param ProcessBuilder    $pb
     * @param string            $name
     * @param array|string|null $value
     */
    protected function processParameter(ProcessBuilder $pb, $name, $value)
    {
        if ($name && '-' === $name[0]) {
            if ($value === true) {
                $this->addParameter($pb, $name);
            } else {
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
     */
    public function getDefaultTimeout()
    {
        return $this->defaultTimeout;
    }

    /**
     * @param int $defaultTimeout
     */
    public function setDefaultTimeout($defaultTimeout)
    {
        $this->defaultTimeout = $defaultTimeout;
    }
}
