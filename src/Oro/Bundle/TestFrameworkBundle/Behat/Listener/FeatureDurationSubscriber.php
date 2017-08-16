<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Listener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FeatureDurationSubscriber implements EventSubscriberInterface
{
    const LOG_FILE = 'feature_duration.json';

    /**
     * @var string
     */
    protected $logDir;

    /**
     * @var float
     */
    protected $startTime;

    /**
     * @var array
     */
    protected $results;

    /**
     * @var array
     */
    protected $projectPathDirectories;

    /**
     * @param string $logDir
     */
    public function __construct($logDir)
    {
        $this->logDir = $logDir;
        $this->projectPathDirectories = explode(DIRECTORY_SEPARATOR, realpath($logDir));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeFeatureTested::BEFORE  => ['setStartTime', 1000],
            AfterFeatureTested::AFTER  => ['measureFeatureDurationTime', -1000],
            AfterExerciseCompleted::AFTER => ['createReport', -1000],
        ];
    }

    public function setStartTime()
    {
        $this->startTime = microtime(true);
    }

    /**
     * @param AfterFeatureTested $event
     */
    public function measureFeatureDurationTime(AfterFeatureTested $event)
    {
        $time = microtime(true) - $this->startTime;
        $featureDirectories = explode(DIRECTORY_SEPARATOR, $event->getFeature()->getFile());
        $featurePath = implode(DIRECTORY_SEPARATOR, array_diff($featureDirectories, $this->projectPathDirectories));

        $this->results[$featurePath] = round($time);
    }

    public function createReport()
    {
        file_put_contents($this->logDir.DIRECTORY_SEPARATOR.self::LOG_FILE, json_encode($this->results));
    }
}
