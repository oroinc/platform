<?php

namespace Oro\Bundle\CronBundle\Engine;

use Oro\Component\Log\OutputLogger;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutor;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Runs isolated process and return output
 */
class CommandRunner implements CommandRunnerInterface
{
    /**
     * @var KernelInterface
     */
    private $kernel;
    private CommandExecutor $commandExecutor;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
        $this->commandExecutor = new CommandExecutor(
            $kernel->getProjectDir().'/bin/console',
            $kernel->getEnvironment()
        );
    }

    /**
     * @param string $commandName
     * @param array $commandArguments
     *
     * @return string
     */
    public function run($commandName, $commandArguments = [])
    {
        if (! $commandArguments) {
            $commandArguments = [];
        }
        if ($commandArguments && ! is_array($commandArguments)) {
            $commandArguments = [$commandArguments];
        }
        $commandArguments['--ignore-errors'] = true;
        $output = new BufferedOutput();
        $this->commandExecutor->runCommand($commandName, $commandArguments, new OutputLogger($output));

        return $output->fetch();
    }
}
