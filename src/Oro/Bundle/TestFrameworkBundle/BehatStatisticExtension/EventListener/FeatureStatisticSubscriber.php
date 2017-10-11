<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Testwork\Counter\Timer;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\Repository\StatisticRepositoryInterface;
use Symfony\Component\EventDispatcher\Event;

final class FeatureStatisticSubscriber implements EventListener
{
    /**
     * @var Timer
     */
    private $timer;

    /**
     * @var StatisticRepositoryInterface
     */
    private $featureRepository;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $buildId;

    /**
     * @var string
     */
    private $gitBranch;

    /**
     * @var string
     */
    private $gitTarget;

    /**
     * FeatureStatisticSubscriber constructor.
     * @param StatisticRepositoryInterface $featureRepository
     * @param string $basePath
     * @param string $buildId
     * @param string $gitBranch
     * @param string $gitTarget
     */
    public function __construct(
        StatisticRepositoryInterface $featureRepository,
        $basePath,
        $buildId,
        $gitBranch,
        $gitTarget
    ) {
        $this->featureRepository = $featureRepository;
        $this->basePath = $basePath;
        $this->buildId = $buildId;
        $this->gitBranch = $gitBranch;
        $this->gitTarget = $gitTarget;
    }

    /**
     * {@inheritdoc}
     */
    public function listenEvent(Formatter $formatter, Event $event, $eventName)
    {
        if ($event instanceof BeforeFeatureTested) {
            $this->record();
        }

        if ($event instanceof AfterFeatureTested) {
            $this->captureStats($event);
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
     * @param AfterFeatureTested $event
     */
    public function captureStats(AfterFeatureTested $event)
    {
        $this->timer->stop();
        $stat = new FeatureStatistic();
        $stat
            ->setBasePath($this->basePath)
            ->setPath($event->getFeature()->getFile())
            ->setTime(round($this->timer->getSeconds()))
            ->setGitBranch($this->gitBranch)
            ->setGitTarget($this->gitTarget)
            ->setBuildId($this->buildId)
        ;

        $this->featureRepository->add($stat);
    }

    public function saveStats()
    {
        $this->featureRepository->flush();
    }
}
