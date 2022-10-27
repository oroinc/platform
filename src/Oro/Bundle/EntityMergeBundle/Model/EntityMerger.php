<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\Step\StepSorter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides functionality to merge entities.
 */
class EntityMerger implements EntityMergerInterface
{
    private iterable $steps;
    private ?array $sortedSteps = null;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(iterable $steps, EventDispatcherInterface $eventDispatcher)
    {
        $this->steps = $steps;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function merge(EntityData $data): void
    {
        if (null === $this->sortedSteps) {
            $this->sortedSteps = StepSorter::getOrderedSteps(
                $this->steps instanceof \Traversable ? iterator_to_array($this->steps) : $this->steps
            );
        }

        $this->eventDispatcher->dispatch(new EntityDataEvent($data), MergeEvents::BEFORE_MERGE_ENTITY);
        foreach ($this->sortedSteps as $step) {
            $step->run($data);
        }
        $this->eventDispatcher->dispatch(new EntityDataEvent($data), MergeEvents::AFTER_MERGE_ENTITY);
    }
}
