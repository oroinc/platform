<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\JobStatusSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Disables message consumer process run in the background based on option
 */
class DoNotRunConsumerOptionController implements Controller
{
    private JobStatusSubscriber $subscriber;

    public function __construct(JobStatusSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command->addOption(
            '--do-not-run-consumer',
            null,
            InputOption::VALUE_NONE,
            'Disable message consumer background running. ' .
            'Should be used when consumer is running by supervisor'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('do-not-run-consumer') &&
            !$input->getOption('check') &&
            !$input->getOption('dry-run') &&
            !$input->getOption('skip-isolators-but-load-fixtures') &&
            !$input->getOption('skip-isolators') &&
            $input->getOption('skip-isolators') !== null &&
            !($input->hasOption('available-suite-sets') && $input->getOption('available-suite-sets'))
        ) {
            return;
        }

        $this->subscriber->doNotRunConsumer();
    }
}
