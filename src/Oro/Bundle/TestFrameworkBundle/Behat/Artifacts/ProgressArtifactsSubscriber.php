<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\Output\Statistics\StepStatV2;
use Behat\Behat\Output\Statistics\TotalStatistics;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prints artifacts links on Behat test fail
 */
class ProgressArtifactsSubscriber implements EventSubscriberInterface
{
    protected TotalStatistics   $statistics;
    protected OutputPrinter     $printer;
    protected array             $artifacts = [];
    protected ScreenshotGenerator $screenshotGenerator;

    public function __construct(
        TotalStatistics $statistics,
        NodeEventListeningFormatter $formatter,
        ScreenshotGenerator $screenshotGenerator
    ) {
        $this->statistics = $statistics;
        $this->printer = $formatter->getOutputPrinter();
        $this->screenshotGenerator = $screenshotGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterScenarioTested::AFTER => ['afterScenario'],
            ExampleTested::AFTER => ['afterScenario'],
            ExerciseCompleted::BEFORE_TEARDOWN => ['printFailedStatistic'],
        ];
    }

    public function afterScenario(AfterScenarioTested $scope)
    {
        if (TestResult::FAILED !== $scope->getTestResult()->getResultCode()) {
            return;
        }

        $scenarioPath = $scope->getFeature()->getFile().':'.$scope->getScenario()->getLine();

        $this->artifacts[$scenarioPath] = $this->screenshotGenerator->capture();
    }

    public function printFailedStatistic()
    {
        $stepStats = $this->statistics->getFailedSteps();

        $this->printer->writeln();
        $this->printer->writeln();
        $this->printer->writeln(sprintf('--- {+%s}%s{-%s}' . PHP_EOL, 'pending', 'Saved artifacts:', 'pending'));

        /** @var StepStatV2 $stepStat */
        foreach ($stepStats as $stepStat) {
            $this->printer->writeln(
                '      {+pending}'.$stepStat->getScenarioText().':{-pending}'
            );
            foreach ($this->artifacts[$stepStat->getScenarioPath()] as $artifact) {
                $this->printer->writeln(
                    '      --- {+pending}'.$artifact.'{-pending}'
                );
            }
        }
    }
}
