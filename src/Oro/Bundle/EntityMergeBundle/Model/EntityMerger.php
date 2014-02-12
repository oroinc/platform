<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityMergeBundle\Model\Step\StepSorter;
use Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface;
use Oro\Bundle\EntityMergeBundle\Event\AfterMergeEvent;
use Oro\Bundle\EntityMergeBundle\Event\BeforeMergeEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;

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
     *
     * @param MergeStepInterface $step
     */
    protected function addMergeStep(MergeStepInterface $step)
    {
        $this->steps[] = $step;
    }

    /**
     * Merge entities
     *
     * @param EntityData $data
     */
    public function merge(EntityData $data)
    {
        $this->eventDispatcher->dispatch(MergeEvents::BEFORE_MERGE, new BeforeMergeEvent($data));

        foreach (StepSorter::getOrderedSteps($this->steps) as $step) {
            $step->run($data);
        }

        $this->eventDispatcher->dispatch(MergeEvents::AFTER_MERGE, new AfterMergeEvent($data));
    }
}
