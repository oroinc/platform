<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResultPrinterSubscriber implements EventSubscriberInterface, HealthCheckerAwareInterface
{
    use HealthCheckerAwareTrait;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
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
        $failedCheckers = array_filter($this->healthCheckers, function (HealthCheckerInterface $healthChecker) {
            return $healthChecker->isFailure();
        });

        if (empty($failedCheckers)) {
            return;
        }

        $this->startPrintingErrors();
        foreach ($failedCheckers as $healthChecker) {
            foreach ($healthChecker->getErrors() as $error) {
                $this->output->writeln(sprintf('<error> - %s</error>', $error));
            }
        }
    }

    private function startPrintingErrors()
    {
        $this->output->writeln('');
        $this->output->writeln('---- Errors while check:');
    }
}
