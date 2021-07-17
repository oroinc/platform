<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface;
use Oro\Bundle\EntityMergeBundle\Model\Step\StepSorter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityMerger implements EntityMergerInterface
{
    /**
     * @var MergeStepInterface[]
     */
    protected $steps = array();

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param MergeStepInterface[] $steps
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(array $steps, EventDispatcherInterface $eventDispatcher)
    {
        foreach ($steps as $step) {
            $this->addMergeStep($step);
        }
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Add merge step
     */
    protected function addMergeStep(MergeStepInterface $step)
    {
        $this->steps[] = $step;
    }

    /**
     * Merge entities
     */
    public function merge(EntityData $data)
    {
        $this->eventDispatcher->dispatch(new EntityDataEvent($data), MergeEvents::BEFORE_MERGE_ENTITY);

        foreach (StepSorter::getOrderedSteps($this->steps) as $step) {
            $step->run($data);
        }

        $this->eventDispatcher->dispatch(new EntityDataEvent($data), MergeEvents::AFTER_MERGE_ENTITY);
    }
}
