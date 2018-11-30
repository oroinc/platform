<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener\MessageQueueIsolationSubscriber;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\EventListener\TestIsolationSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Testwork console controller
 */
class InputOutputController implements Controller
{
    /** @var  TestIsolationSubscriber */
    protected $testIsolationSubscriber;

    /** @var MessageQueueIsolationSubscriber */
    private $messageQueueIsolationSubscriber;

    /**
     * @param TestIsolationSubscriber $testIsolationSubscriber
     * @param MessageQueueIsolationSubscriber $messageQueueIsolationSubscriber
     */
    public function __construct(
        TestIsolationSubscriber $testIsolationSubscriber,
        MessageQueueIsolationSubscriber $messageQueueIsolationSubscriber
    ) {
        $this->testIsolationSubscriber = $testIsolationSubscriber;
        $this->messageQueueIsolationSubscriber = $messageQueueIsolationSubscriber;
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

        $this->messageQueueIsolationSubscriber->setOutput($output);
        $this->messageQueueIsolationSubscriber->setSkip($skip);
    }
}
