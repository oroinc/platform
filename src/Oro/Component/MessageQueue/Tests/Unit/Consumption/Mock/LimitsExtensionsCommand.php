<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Mock;

use Oro\Component\MessageQueue\Consumption\LimitsExtensionsCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LimitsExtensionsCommand extends Command
{
    use LimitsExtensionsCommandTrait;

    protected $extensions;

    protected function configure()
    {
        parent::configure();

        $this->configureLimitsExtensions();
    }

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
