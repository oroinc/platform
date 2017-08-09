<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Listener\FeatureDurationSubscriber;
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
    protected $stepDurationSubscriber;

    /**
     * @var FeatureDurationSubscriber
     */
    protected $featureDurationSubscriber;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @param StepDurationMeasureSubscriber $stepDurationSubscriber
     * @param FeatureDurationSubscriber $featureDurationSubscriber
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        StepDurationMeasureSubscriber $stepDurationSubscriber,
        FeatureDurationSubscriber $featureDurationSubscriber,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->stepDurationSubscriber = $stepDurationSubscriber;
        $this->featureDurationSubscriber = $featureDurationSubscriber;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--show-execution-time',
                null,
                InputOption::VALUE_NONE,
                'Measure execution time for each step and show results in table sorted by average time'
            )
            ->addOption(
                '--log-feature-execution-time',
                null,
                InputOption::VALUE_NONE,
                'Measure and log execution time for each feature'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('show-execution-time')) {
            $this->stepDurationSubscriber->setOutput($output);
            $this->eventDispatcher->addSubscriber($this->stepDurationSubscriber);
        }

        if ($input->getOption('log-feature-execution-time')) {
            $this->eventDispatcher->addSubscriber($this->featureDurationSubscriber);
        }
    }
}
