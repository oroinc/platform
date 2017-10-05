<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer;

use Behat\Behat\Output\Node\Printer\FeaturePrinter;
use Behat\Behat\Output\Statistics\PhaseStatistics;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\JUnitOutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\EventListener\JUnitDurationListener;

final class JUnitFeaturePrinter implements FeaturePrinter
{
    /**
     * @var PhaseStatistics
     */
    private $statistics;

    /**
     * @var JUnitDurationListener
     */
    private $durationListener;

    public function __construct(PhaseStatistics $statistics, JUnitDurationListener $durationListener)
    {
        $this->statistics = $statistics;
        $this->durationListener = $durationListener;
    }

    /**
     * {@inheritDoc}
     */
    public function printHeader(Formatter $formatter, FeatureNode $feature)
    {
        $stats = $this->statistics->getScenarioStatCounts();

        if (0 === count($stats)) {
            $totalCount = 0;
        } else {
            $totalCount = array_sum($stats);
        }

        /** @var JUnitOutputPrinter $outputPrinter */
        $outputPrinter = $formatter->getOutputPrinter();

        $outputPrinter->addTestsuite([
            'name' => $feature->getTitle(),
            'tests' => $totalCount,
            'skipped' => $stats[TestResult::SKIPPED],
            'failures' => $stats[TestResult::FAILED],
            'errors' => $stats[TestResult::PENDING] + $stats[StepResult::UNDEFINED],
            'time' => $this->durationListener->getFeatureDuration($feature)
        ]);
        $this->statistics->reset();
    }

    /**
     * {@inheritDoc}
     */
    public function printFooter(Formatter $formatter, TestResult $result)
    {
    }
}
