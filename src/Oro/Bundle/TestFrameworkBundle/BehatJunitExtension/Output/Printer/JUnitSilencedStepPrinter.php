<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer;

use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\StepResult;
use Behat\Gherkin\Node\ScenarioLikeInterface as Scenario;
use Behat\Gherkin\Node\StepNode;
use Behat\Testwork\Output\Formatter;
use Oro\Bundle\TestFrameworkBundle\Behat\ServiceContainer\SkipOnFailureStepTester;

/**
 * Prints steps that were silenced by the SkipOnFailureStepTester.
 */
class JUnitSilencedStepPrinter implements StepPrinter
{
    public function __construct(private StepPrinter $parentPrinter)
    {
    }

    #[\Override]
    public function printStep(Formatter $formatter, Scenario $scenario, StepNode $step, StepResult $result)
    {
        $outputPrinter = $formatter->getOutputPrinter();

        if ($result instanceof ExecutedStepResult && $result->getCallResult()->hasStdOut()) {
            $stdOut = $result->getCallResult()->getStdOut();
            if (str_starts_with($stdOut, SkipOnFailureStepTester::SILENCED_STEP_MESSAGE_PREFIX)) {
                $stdOut = substr($stdOut, strlen(SkipOnFailureStepTester::SILENCED_STEP_MESSAGE_PREFIX));
                $outputPrinter->addTestcaseChild('skipped', ['message' => $stdOut]);
            }
        }

        return $this->parentPrinter->printStep($formatter, $scenario, $step, $result);
    }
}
