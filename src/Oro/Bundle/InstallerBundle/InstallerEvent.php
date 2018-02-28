<?php

namespace Oro\Bundle\InstallerBundle;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * An event for install or update commands
 */
class InstallerEvent extends ConsoleEvent
{
    /** @var CommandExecutor */
    protected $commandExecutor;

    /**
     * @param Command $command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param CommandExecutor $commandExecutor
     */
    public function __construct(
        Command $command,
        InputInterface $input,
        OutputInterface $output,
        CommandExecutor $commandExecutor
    ) {
        parent::__construct($command, $input, $output);

        $this->commandExecutor = $commandExecutor;
    }

    /**
     * @return CommandExecutor
     */
    public function getCommandExecutor(): CommandExecutor
    {
        return $this->commandExecutor;
    }
}
