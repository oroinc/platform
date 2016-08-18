<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\StepDurationMeasureSubscriber;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MeasureExecutionTimeController implements Controller
{
    /**
     * @var StepDurationMeasureSubscriber
     */
    protected $subscriber;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param StepDurationMeasureSubscriber $subscriber
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(StepDurationMeasureSubscriber $subscriber, EventDispatcherInterface $eventDispatcher)
    {
        $this->subscriber = $subscriber;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command->addOption(
            '--show-execution-time',
            null,
            InputOption::VALUE_NONE,
            'Measure execution time for each step and show results in table sorted by average time'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('show-execution-time')) {
            return;
        }

        $this->subscriber->setOutput($output);
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }
}
