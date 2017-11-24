<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\AfterFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Testwork\Counter\Timer;
use Behat\Testwork\EventDispatcher\Event\AfterExerciseCompleted;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Node\EventListener\EventListener;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider\CriteriaArrayCollection;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository\BatchRepositoryInterface;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\FeaturePathLocator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\Event;

final class FeatureStatisticSubscriber implements EventListener
{
    /**
     * @var Timer
     */
    private $timer;

    /**
     * @var BatchRepositoryInterface
     */
    private $featureRepository;

    /**
     * @var FeaturePathLocator
     */
    private $featurePathLocator;

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
     * @var bool
     */
    private $skip = false;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * FeatureStatisticSubscriber constructor.
     * @param BatchRepositoryInterface $featureRepository
     * @param FeaturePathLocator $featurePathLocator
     * @param CriteriaArrayCollection $criteria
     */
    public function __construct(
        BatchRepositoryInterface $featureRepository,
        FeaturePathLocator $featurePathLocator,
        CriteriaArrayCollection $criteria
    ) {
        $this->featureRepository = $featureRepository;
        $this->featurePathLocator = $featurePathLocator;
        $this->buildId = $criteria->get('build_id');
        $this->gitBranch = $criteria->get('branch_name') ?: $criteria->get('single_branch_name');
        $this->gitTarget = $criteria->get('target_branch');
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
        if (!$event->getTestResult()->isPassed()) {
            return;
        }

        $this->timer->stop();

        $stat = new FeatureStatistic();
        $stat
            ->setPath($this->featurePathLocator->getRelativePath($event->getFeature()->getFile()))
            ->setTime(round($this->timer->getTime()))
            ->setGitBranch($this->gitBranch)
            ->setGitTarget($this->gitTarget)
            ->setBuildId($this->buildId)
        ;

        $this->featureRepository->add($stat);
    }

    public function saveStats()
    {
        try {
            $this->featureRepository->flush();
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

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param bool $skip
     */
    public function setSkip(bool $skip)
    {
        $this->skip = $skip;
    }
}
