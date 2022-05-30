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
    private TestIsolationSubscriber $testIsolationSubscriber;
    private MessageQueueIsolationSubscriber $messageQueueIsolationSubscriber;

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
                "Comma separated list of isolator tags to skip. If the value is not provided, all are skipped. \n".
                '(available isolator tags are: <comment>'.implode(',', $this->getIsolatorTags()).'</comment>)',
                false
            )
            ->addOption(
                '--skip-isolators-but-load-fixtures',
                null,
                InputOption::VALUE_NONE,
                'Skip all isolators except the "doctrine" that loads data fixtures'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // $input->getOption('skip-isolators') returns:
        //  - null - when the option is provided without the value
        //  - false - when the option is not provided
        $skipAllIsolators = $input->getOption('skip-isolators') === null || $input->getOption('dry-run');

        if ($input->getOption('skip-isolators')) {
            $skipIsolatorsTags = array_map('trim', explode(',', $input->getOption('skip-isolators')));
        } else {
            $skipIsolatorsTags = [];
        }

        if ($input->getOption('skip-isolators-but-load-fixtures')) {
            $skipAllIsolators = false;
            $skipIsolatorsTags = array_diff($this->getIsolatorTags(), ['doctrine']);
        }

        $this->testIsolationSubscriber->setInput($input);
        $this->testIsolationSubscriber->setOutput($output);
        $this->testIsolationSubscriber->skipIsolatorsTags($skipIsolatorsTags);
        if ($skipAllIsolators) {
            $this->testIsolationSubscriber->skip();
        }

        $this->messageQueueIsolationSubscriber->setOutput($output);
        if ($skipAllIsolators || in_array(MessageQueueIsolationSubscriber::TAG, $skipIsolatorsTags)) {
            $this->messageQueueIsolationSubscriber->skip();
        }
    }

    private function getIsolatorTags(): array
    {
        return [...$this->testIsolationSubscriber->getIsolatorsTags(), MessageQueueIsolationSubscriber::TAG];
    }
}
