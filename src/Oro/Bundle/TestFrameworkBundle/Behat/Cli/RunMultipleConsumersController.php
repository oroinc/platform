<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\JobStatusSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Choosing the number of consumers to run based on the option
 */
class RunMultipleConsumersController implements Controller
{
    private JobStatusSubscriber $subscriber;

    public function __construct(JobStatusSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function configure(SymfonyCommand $command)
    {
        $command->addOption(
            '--consumers',
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of consumers to run in the background',
            2
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('consumers')) {
            return;
        }

        $this->subscriber->setCountConsumersFromOption($input->getOption('consumers'));
    }
}
