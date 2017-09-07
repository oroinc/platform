<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResultPrinterSubscriber implements EventSubscriberInterface
{
    /**
     * @var HealthCheckerInterface[]
     */
    protected $healthCheckers = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var bool
     */
    private $isPrinted = false;

    /**
     * @param HealthCheckerInterface[] $healthCheckers
     * @param OutputInterface $output
     */
    public function __construct(array $healthCheckers, OutputInterface $output)
    {
        $this->healthCheckers = $healthCheckers;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterExerciseCompleted::BEFORE_TEARDOWN => ['printMessages'],
        ];
    }

    public function printMessages()
    {
        foreach ($this->healthCheckers as $healthChecker) {
            if (!$healthChecker->isFailure()) {
                continue;
            }

            foreach ($healthChecker->getErrors() as $error) {
                $this->startPrintingErrors();
                $this->output->writeln(sprintf('<error> - %s</error>', $error));
            }
        }
    }

    private function startPrintingErrors()
    {
        if ($this->isPrinted) {
            return;
        }

        $this->output->writeln('');
        $this->output->writeln('---- Errors while check:');
        $this->isPrinted = true;
    }
}
