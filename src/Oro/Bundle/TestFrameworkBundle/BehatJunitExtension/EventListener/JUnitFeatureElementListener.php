<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepSetup;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Output\Node\Printer\FeaturePrinter;
use Behat\Behat\Output\Node\Printer\SetupPrinter;
use Behat\Behat\Output\Node\Printer\StepPrinter;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\EventDispatcher\Event\AfterSetup;
use Behat\Testwork\Output\Formatter;
use Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\Output\Printer\JUnitScenarioPrinter;
use Symfony\Component\EventDispatcher\Event;

class JUnitFeatureElementListener
{
    /**
     * @var FeaturePrinter
     */
    private $featurePrinter;
    /**
     * @var JUnitScenarioPrinter
     */
    private $scenarioPrinter;
    /**
     * @var StepPrinter
     */
    private $stepPrinter;
    /**
     * @var SetupPrinter
     */
    private $setupPrinter;
    /**
     * @var FeatureNode
     */
    private $beforeFeatureTestedEvent;
    /**
     * @var AfterScenarioTested[]
     */
    private $afterScenarioTestedEvents = [];
    /**
     * @var AfterStepTested[]
     */
    private $afterStepTestedEvents = [];
    /**
     * @var AfterSetup[]
     */
    private $afterStepSetupEvents = [];

    /**
     * Initializes listener.
     *
     * @param FeaturePrinter $featurePrinter
     * @param JUnitScenarioPrinter $scenarioPrinter
     * @param StepPrinter $stepPrinter
     * @param SetupPrinter $setupPrinter
     */
    public function __construct(
        FeaturePrinter $featurePrinter,
        JUnitScenarioPrinter $scenarioPrinter,
        StepPrinter $stepPrinter,
        SetupPrinter $setupPrinter
    ) {
        $this->featurePrinter = $featurePrinter;
        $this->scenarioPrinter = $scenarioPrinter;
        $this->stepPrinter = $stepPrinter;
        $this->setupPrinter = $setupPrinter;
    }

    /**
     * {@inheritdoc}
     */
    public function listenEvent(Formatter $formatter, Event $event, $eventName)
    {
        if ($event instanceof ScenarioTested) {
            $this->captureScenarioEvent($event);
        }

        if ($event instanceof StepTested
            || $event instanceof AfterStepSetup
        ) {
            $this->captureStepEvent($event);
        }

        $this->captureFeatureOnBeforeEvent($event);
        $this->printFeatureOnAfterEvent($formatter, $event);
    }

    /**
     * Captures scenario tested event.
     *
     * @param ScenarioTested $event
     */
    private function captureScenarioEvent(ScenarioTested $event)
    {
        if ($event instanceof AfterScenarioTested) {
            $this->afterScenarioTestedEvents[$event->getScenario()->getLine()] = [
                'event'             => $event,
                'step_events'       => $this->afterStepTestedEvents,
                'step_setup_events' => $this->afterStepSetupEvents,
            ];

            $this->afterStepTestedEvents = [];
            $this->afterStepSetupEvents = [];
        }
    }

    /**
     * Captures feature on BEFORE event.
     *
     * @param Event $event
     */
    private function captureFeatureOnBeforeEvent(Event $event)
    {
        if (!$event instanceof BeforeFeatureTested) {
            return;
        }

        $this->beforeFeatureTestedEvent = $event->getFeature();
    }

    /**
     * Captures step tested event.
     *
     * @param Event $event
     */
    private function captureStepEvent(Event $event)
    {
        if ($event instanceof AfterStepTested) {
            $this->afterStepTestedEvents[$event->getStep()->getLine()] = $event;
        }
        if ($event instanceof AfterStepSetup) {
            $this->afterStepSetupEvents[$event->getStep()->getLine()] = $event;
        }
    }

    /**
     * Prints the feature on AFTER event.
     *
     * @param Formatter $formatter
     * @param Event     $event
     */
    public function printFeatureOnAfterEvent(Formatter $formatter, Event $event)
    {
        if (!$event instanceof AfterFeatureTested) {
            return;
        }

        $this->featurePrinter->printHeader($formatter, $this->beforeFeatureTestedEvent);

        foreach ($this->afterScenarioTestedEvents as $afterScenario) {
            $afterScenarioTested = $afterScenario['event'];
            $this->scenarioPrinter->printOpenTag(
                $formatter,
                $afterScenarioTested->getFeature(),
                $afterScenarioTested->getScenario(),
                $afterScenarioTested->getTestResult()
            );

            /** @var AfterStepSetup $afterStepSetup */
            foreach ($afterScenario['step_setup_events'] as $afterStepSetup) {
                $this->setupPrinter->printSetup($formatter, $afterStepSetup->getSetup());
            }
            foreach ($afterScenario['step_events'] as $afterStepTested) {
                $this->stepPrinter->printStep(
                    $formatter,
                    $afterScenarioTested->getScenario(),
                    $afterStepTested->getStep(),
                    $afterStepTested->getTestResult()
                );
                $this->setupPrinter->printTeardown($formatter, $afterStepTested->getTeardown());
            }
        }

        $this->featurePrinter->printFooter($formatter, $event->getTestResult());
        $this->afterScenarioTestedEvents = [];
    }
}
