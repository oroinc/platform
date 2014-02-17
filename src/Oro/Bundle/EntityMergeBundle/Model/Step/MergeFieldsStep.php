<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;

class MergeFieldsStep implements DependentMergeStepInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param StrategyInterface $strategy
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(StrategyInterface $strategy, EventDispatcherInterface $eventDispatcher)
    {
        $this->strategy = $strategy;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Merge fields
     *
     * @param EntityData $data
     */
    public function run(EntityData $data)
    {
        foreach ($data->getFields() as $field) {
            $this->eventDispatcher->dispatch(MergeEvents::BEFORE_MERGE_FIELD, new FieldDataEvent($field));
            $this->strategy->merge($field);
            $this->eventDispatcher->dispatch(MergeEvents::AFTER_MERGE_FIELD, new FieldDataEvent($field));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependentSteps()
    {
        return array('Oro\\Bundle\\EntityMergeBundle\\Model\\Step\\ValidateStep');
    }
}
