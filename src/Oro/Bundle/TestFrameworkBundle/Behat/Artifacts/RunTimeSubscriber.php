<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\Output\NodeEventListeningFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
