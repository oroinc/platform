<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener\TestIsolationSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InputOutputController implements Controller
{
    /** @var  TestIsolationSubscriber */
    protected $testIsolationSubscriber;

    public function __construct(TestIsolationSubscriber $testIsolationSubscriber)
    {
        $this->testIsolationSubscriber = $testIsolationSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--skip-isolators',
                null,
                InputOption::VALUE_OPTIONAL,
                '',
                'database,cache,message-queue,inital_message_queue,doctrine,import_export'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var bool $skip */
        $skip = $input->getOption('dry-run');
        $skipIsolators = false === $input->hasParameterOption('--skip-isolators')
            ? ''
            : $input->getOption('skip-isolators');

        $skipIsolators = array_map('trim', explode(',', $skipIsolators));
        $this->testIsolationSubscriber->setInput($input);
        $this->testIsolationSubscriber->setOutput($output);
        $this->testIsolationSubscriber->setSkip($skip);
        $this->testIsolationSubscriber->setSkipIsolators($skipIsolators);
    }
}
