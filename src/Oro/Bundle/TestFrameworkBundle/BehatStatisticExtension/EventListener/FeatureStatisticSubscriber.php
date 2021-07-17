<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Testwork\Counter\Timer;
use Behat\Testwork\Event\Event;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatisticManager;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Collects statistic information and stores it to DB.
 */
final class FeatureStatisticSubscriber implements EventListener
{
    /**
     * @var Timer
     */
    private $timer;

    /**
     * @var FeatureStatisticManager
     */
    private $statisticManager;

    /**
     * @var bool
     */
    private $skip = false;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(FeatureStatisticManager $statisticManager)
    {
        $this->statisticManager = $statisticManager;
    }

    /**
     * {@inheritdoc}
     */
    public function listenEvent(Formatter $formatter, Event $event, $eventName)
    {
        if ($this->skip) {
            return;
        }

        if ($event instanceof BeforeFeatureTested) {
            $this->record();
        }

        if ($event instanceof AfterFeatureTested) {
            $this->captureStats($event);
            $this->saveStats();
        }

        if ($event instanceof AfterExerciseCompleted) {
            $this->saveStats();
        }
    }

    /**
     * Start tracking
     */
    public function record()
    {
        $this->timer = new Timer();
        $this->timer->start();
    }

    /**
     * Finish tracking and save stats
     */
    public function captureStats(AfterFeatureTested $event)
    {
        if (!$event->getTestResult()->isPassed()) {
            return;
        }

        $this->timer->stop();

        $this->statisticManager->addStatistic($event->getFeature(), round($this->timer->getTime()));
    }

    public function saveStats()
    {
        try {
            $this->statisticManager->saveStatistics();

            $this->output->writeln('<info>Statistics was recorded successfully.</info>');
        } catch (\Exception $e) {
            // We should pass the tests even if we are unavailable record the statistics
            if ($this->output) {
                $this->output->writeln(sprintf(
                    '<error>Exception while record the statistics:%s%s</error>',
                    PHP_EOL,
                    $e->getMessage()
                ));
            }
        }
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function setSkip(bool $skip)
    {
        $this->skip = $skip;
    }
}
