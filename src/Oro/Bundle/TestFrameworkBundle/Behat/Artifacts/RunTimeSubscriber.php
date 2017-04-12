<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\Output\Statistics\TotalStatistics;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\BeforeExerciseCompleted;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Behat\Testwork\Output\OutputManager;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RunTimeSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ProgressArtifactsSubscriber
     */
    protected $progressArtifactsSubscriber;

    /**
     * @var PrettyArtifactsSubscriber
     */
    protected $prettyArtifactsSubscriber;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param ProgressArtifactsSubscriber $progressArtifactsSubscriber
     * @param PrettyArtifactsSubscriber $prettyArtifactsSubscriber
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ProgressArtifactsSubscriber $progressArtifactsSubscriber,
        PrettyArtifactsSubscriber $prettyArtifactsSubscriber
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->progressArtifactsSubscriber = $progressArtifactsSubscriber;
        $this->prettyArtifactsSubscriber = $prettyArtifactsSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ExerciseCompleted::BEFORE => ['identifySubscriber', 100],
        ];
    }

    public function identifySubscriber()
    {
        foreach ($this->eventDispatcher->getListeners(AfterScenarioTested::AFTER) as $listener) {
            $listener = $listener[0];
            if (NodeEventListeningFormatter::class === get_class($listener)) {
                /** @var NodeEventListeningFormatter $listener */
                if ('pretty' === $listener->getName()) {
                    $this->eventDispatcher->addSubscriber($this->prettyArtifactsSubscriber);
                } elseif ('progress' === $listener->getName()) {
                    $this->eventDispatcher->addSubscriber($this->progressArtifactsSubscriber);
                }
            }
        }
    }
}
