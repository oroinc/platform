<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Mock;

use Oro\Component\MessageQueue\Consumption\LimitsExtensionsCommandTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'name',
    description: 'description',
    hidden: false,
)]
class LimitsExtensionsCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    protected $extensions;

    #[\Override]
    protected function configure()
    {
        parent::configure();

        $this->configureLimitsExtensions();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->extensions = $this->getLimitsExtensions($input, $output);

        return Command::SUCCESS;
    }

    public function getExtensions()
    {
        return $this->extensions;
    }
}
