<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Prints artifacts links on Behat step fail
 */
class PrettyArtifactsSubscriber implements EventSubscriberInterface
{
    protected bool $isOutline = false;
    protected OutputPrinter $printer;
    protected ScreenshotGenerator $screenshotGenerator;

    public function __construct(NodeEventListeningFormatter $formatter, ScreenshotGenerator $screenshotGenerator)
    {
        $this->printer = $formatter->getOutputPrinter();
        $this->screenshotGenerator = $screenshotGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            AfterStepTested::AFTER => ['afterStep'],
            OutlineTested::BEFORE  => ['beforeOutline', 1500],
            OutlineTested::AFTER   => ['afterOutline', 1500],
            ExampleTested::AFTER   => ['afterExample', 1500],
        ];
    }

    public function beforeOutline()
    {
        $this->isOutline = true;
    }

    public function afterOutline()
    {
        $this->isOutline = false;
    }

    public function afterStep(AfterStepTested $scope)
    {
        if (TestResult::FAILED !== $scope->getTestResult()->getResultCode()) {
            return;
        }

        if ($this->isOutline) {
            return;
        }

        $this->saveArtifacts();
    }

    public function afterExample(AfterScenarioTested $scope)
    {
        if (TestResult::FAILED !== $scope->getTestResult()->getResultCode()) {
            return;
        }

        $this->saveArtifacts();
    }

    public function saveArtifacts()
    {
        $this->printer->writeln(sprintf('      {+%s}+-- %s{-%s}', 'pending', 'Saved artifacts:', 'pending'));
        foreach ($this->screenshotGenerator->capture() as $url) {
            $this->printer->writeln('      {+pending}' . $url . '{-pending}');
        }
    }
}
