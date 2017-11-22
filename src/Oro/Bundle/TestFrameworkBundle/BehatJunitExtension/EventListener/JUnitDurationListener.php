<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatJunitExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\KeywordNodeInterface;
use Behat\Gherkin\Node\ScenarioLikeInterface;
use Behat\Testwork\Counter\Timer;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Symfony\Component\EventDispatcher\Event;

final class JUnitDurationListener implements EventListener
{
    /**
     * @var Timer[]
     */
    private $scenarioTimerStore = [];

    /**
     * @var Timer[]
     */
    private $featureTimerStore = [];

    /**
     * @var array key hash of node; value time in seconds
     */
    private $resultStore = [];

    /**
     * @var array key hash of node; value time in seconds
     */
    private $featureResultStore = [];

    /** @inheritdoc */
    public function listenEvent(Formatter $formatter, Event $event, $eventName)
    {
        if ($event instanceof BeforeScenarioTested) {
            $this->captureBeforeScenarioEvent($event);
        }

        if ($event instanceof BeforeFeatureTested) {
            $this->captureBeforeFeatureTested($event);
        }

        if ($event instanceof AfterScenarioTested) {
            $this->captureAfterScenarioEvent($event);
        }

        if ($event instanceof AfterFeatureTested) {
            $this->captureAfterFeatureEvent($event);
        }
    }

    /**
     * @param ScenarioLikeInterface $scenario
     * @return mixed|string
     */
    public function getDuration(ScenarioLikeInterface $scenario)
    {
        $key = $this->getHash($scenario);
        return array_key_exists($key, $this->resultStore) ? $this->resultStore[$key] : '';
    }

    /**
     * @param FeatureNode $feature
     * @return mixed|string
     */
    public function getFeatureDuration(FeatureNode $feature)
    {
        $key = $this->getHash($feature);
        return array_key_exists($key, $this->featureResultStore) ? $this->featureResultStore[$key] : '';
    }

    /**
     * @param BeforeFeatureTested $event
     */
    private function captureBeforeFeatureTested(BeforeFeatureTested $event)
    {
        $this->featureTimerStore[$this->getHash($event->getFeature())] = $this->startTimer();
    }

    /**
     * @param BeforeScenarioTested $event
     */
    private function captureBeforeScenarioEvent(BeforeScenarioTested $event)
    {
        $this->scenarioTimerStore[$this->getHash($event->getScenario())] = $this->startTimer();
    }

    /**
     * @param AfterScenarioTested $event
     */
    private function captureAfterScenarioEvent(AfterScenarioTested $event)
    {
        $key = $this->getHash($event->getScenario());
        $timer = $this->scenarioTimerStore[$key];
        if ($timer instanceof Timer) {
            $timer->stop();
            $this->resultStore[$key] = round($timer->getTime());
        }
    }

    /**
     * @param AfterFeatureTested $event
     */
    private function captureAfterFeatureEvent(AfterFeatureTested $event)
    {
        $key = $this->getHash($event->getFeature());
        $timer = $this->featureTimerStore[$key];
        if ($timer instanceof Timer) {
            $timer->stop();
            $this->featureResultStore[$key] = round($timer->getTime());
        }
    }

    /**
     * @param KeywordNodeInterface $node
     * @return string
     */
    private function getHash(KeywordNodeInterface $node)
    {
        return spl_object_hash($node);
    }

    /**
     * @return Timer
     */
    private function startTimer()
    {
        $timer = new Timer();
        $timer->start();

        return $timer;
    }
}
