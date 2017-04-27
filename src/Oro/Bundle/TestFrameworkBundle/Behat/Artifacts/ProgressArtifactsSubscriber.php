<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\Output\Statistics\StepStatV2;
use Behat\Behat\Output\Statistics\TotalStatistics;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProgressArtifactsSubscriber implements EventSubscriberInterface
{
    /**
     * @var TotalStatistics
     */
    protected $statistics;

    /**
     * @var OutputPrinter
     */
    protected $printer;

    /**
     * @var ArtifactsHandlerInterface[]
     */
    protected $artifactsHandlers;

    /**
     * @var Mink
     */
    protected $mink;

    /**
     * @var array
     */
    protected $artifacts;

    public function __construct(TotalStatistics $statistics, NodeEventListeningFormatter $formatter, Mink $mink)
    {
        $this->statistics = $statistics;
        $this->printer = $formatter->getOutputPrinter();
        $this->mink = $mink;
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

    /**
     * @param ArtifactsHandlerInterface $artifactsHandler
     */
    public function addArtifactHandler(ArtifactsHandlerInterface $artifactsHandler)
    {
        $this->artifactsHandlers[] = $artifactsHandler;
    }

    public function afterScenario(AfterScenarioTested $scope)
    {
        if (TestResult::FAILED !== $scope->getTestResult()->getResultCode()) {
            return;
        }

        $scenarioPath = $scope->getFeature()->getFile().':'.$scope->getScenario()->getLine();

        foreach ($this->artifactsHandlers as $artifactsHandler) {
            $artifact = $artifactsHandler->save($this->mink->getSession()->getScreenshot());
            $this->artifacts[$scenarioPath][] = $artifact;
        }
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
