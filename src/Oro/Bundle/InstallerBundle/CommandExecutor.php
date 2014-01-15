<?php

namespace Oro\Bundle\InstallerBundle;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

class CommandExecutor
{
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
     * Constructor
     *
     * @param string|null     $env
     * @param OutputInterface $output
     * @param Application     $application
     */
    public function __construct($env, OutputInterface $output, Application $application)
    {
        $this->env         = $env;
        $this->output      = $output;
        $this->application = $application;
    }

    /**
     * Launches a command.
     * If '--process-isolation' parameter is specified the command will be launched as a separate process.
     * In this case you can parameter '--process-timeout' to set the process timeout
     * in seconds. Default timeout is 60 seconds.
     * If '--ignore-errors' parameter is specified any errors are ignored;
     * otherwise, an exception is raises if an error happened.
     *
     * @param string $command
     * @param array  $params
     * @return CommandExecutor
     * @throws \RuntimeException if command failed and '--ignore-errors' parameter is not specified
     */
    public function runCommand($command, $params = array())
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

        if (array_key_exists('--process-isolation', $params)) {
            unset($params['--process-isolation']);
            $phpFinder = new PhpExecutableFinder();
            $php       = $phpFinder->find();
            $pb        = new ProcessBuilder();
            $pb
                ->add($php)
                ->add($_SERVER['argv'][0]);

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

            $output = $this->output;
            $process->run(
                function ($type, $data) use ($output) {
                    $output->write($data);
                }
            );
            $ret = $process->getExitCode();
        } else {
            $this->application->setAutoExit(false);
            $ret = $this->application->run(new ArrayInput($params), $this->output);
        }

        if (0 !== $ret) {
            if ($ignoreErrors) {
                $this->output->writeln(
                    sprintf('<error>The command terminated with an error code: %u.</error>', $ret)
                );
            } else {
                throw new \RuntimeException(
                    sprintf('The command terminated with an error status: %u.', $ret)
                );
            }
        }

        return $this;
    }

    /**
     * @param ProcessBuilder $processBuilder
     * @param string $name
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
}
