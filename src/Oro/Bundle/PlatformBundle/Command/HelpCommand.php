<?php

namespace Oro\Bundle\PlatformBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand as SymfonyHelpCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelpCommand extends SymfonyHelpCommand
{
    /**
     * @var Command|null
     */
    protected $command;

    /**
     * {@inheritdoc}
     */
    public function setCommand(Command $command)
    {
        parent::setCommand($command);
        $this->command = $command;
    }

    /**
     * @return Command|null
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $this->command = null;
    }
}
