<?php

namespace Oro\Bundle\InstallerBundle\Process;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

class CommandExecutor
{
    /**
     * @var InputInterface
     */
    protected $input;

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param Application     $application
     */
    public function __construct(InputInterface $input, OutputInterface $output, Application $application)
    {
        $this->input  = $input;
        $this->output = $output;
        $this->application = $application;
    }

    /**
     * Launches a command.
     * If '--process-isolation' parameter is specified the command will be launched as a separate process.
     * In this case you can parameter '--process-timeout' to set the process timeout
     * in seconds. Default timeout is 60 seconds.
     *
     * @param string          $command
     * @param array           $params
     * @return CommandExecutor
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
        if ($this->input->hasOption('env') && $this->input->getOption('env') !== 'dev') {
            $params['--env'] = $this->input->getOption('env');
        }

        if (array_key_exists('--process-isolation', $params)) {
            unset($params['--process-isolation']);
            $phpFinder = new PhpExecutableFinder();
            $php = $phpFinder->find();
            $pb = new ProcessBuilder();
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
                        $pb->add($param);
                    } else {
                        $pb->add($param . '=' . $val);
                    }
                } else {
                    $pb->add($val);
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
            $this->output->writeln(sprintf('<error>The command terminated with an error status (%s)</error>', $ret));
        }

        return $this;
    }
}
