<?php

namespace Oro\Bundle\CronBundle\Engine;

use Oro\Component\Log\OutputLogger;
use Oro\Component\PhpUtils\Tools\CommandExecutor\CommandExecutor;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Runs isolated process and return output
 */
class CommandRunner implements CommandRunnerInterface
{
    private CommandExecutor $commandExecutor;

    public function __construct(string $projectDir, string $environment)
    {
        $this->commandExecutor = new CommandExecutor($projectDir . '/bin/console', $environment);
    }

    /**
     * @param string $commandName
     * @param array $commandArguments
     *
     * @return string
     */
    #[\Override]
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
