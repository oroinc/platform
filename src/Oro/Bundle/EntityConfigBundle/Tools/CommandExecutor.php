<?php

namespace Oro\Bundle\EntityConfigBundle\Tools;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class CommandExecutor
{
    /**
     * @var string
     */
    protected $consoleCmdPath;

    /**
     * @var string
     */
    protected $env;

    /**
     * Constructor
     *
     * @param string $consoleCmdPath
     * @param string $env
     */
    public function __construct($consoleCmdPath, $env)
    {
        $this->consoleCmdPath = $consoleCmdPath;
        $this->env            = $env;
    }

    /**
     * Launches a command as a separate process.
     *
     * The '--process-timeout' parameter can be used to set the process timeout
     * in seconds. Default timeout is 60 seconds.
     * If '--ignore-errors' parameter is specified any errors are ignored;
     * otherwise, an exception is raises if an error happened.
     *
     * @param string               $command
     * @param array                $params
     * @param LoggerInterface|null $logger
     *
     * @return integer The exit status code
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function runCommand($command, $params = [], LoggerInterface $logger = null)
    {
        $params = array_merge(
            array(
                'command'    => $command,
                '--no-debug' => true,
            ),
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
        $pb->add($this->getPhp())->add($this->consoleCmdPath);

        if (array_key_exists('--process-timeout', $params)) {
            $pb->setTimeout($params['--process-timeout']);
            unset($params['--process-timeout']);
        }

        foreach ($params as $param => $val) {
            if ($param && '-' === $param[0]) {
                if ($val === true) {
                    $this->addParameter($pb, $param);
                } else {
                    $this->addParameter($pb, $param, $val);
                }
            } else {
                $this->addParameter($pb, $val);
            }
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
        if (0 !== $exitCode) {
            if ($ignoreErrors) {
                $logger->warning(sprintf('The command terminated with an exit code: %u.', $exitCode));
            } else {
                throw new \RuntimeException(sprintf('The command terminated with an exit code: %u.', $exitCode));
            }
        }

        return $exitCode;
    }

    /**
     * @param ProcessBuilder    $processBuilder
     * @param string            $name
     * @param array|string|null $value
     */
    protected function addParameter(ProcessBuilder $processBuilder, $name, $value = null)
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
            $processBuilder->add($parameter);
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
}
