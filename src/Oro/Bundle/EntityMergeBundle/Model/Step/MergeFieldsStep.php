<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Step;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Merge handler step for merging result data
 */
class MergeFieldsStep implements DependentMergeStepInterface
{
    public function __construct(private StrategyInterface $strategy, private EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * Merge fields
     */
    public function run(EntityData $data)
    {
        foreach ($data->getFields() as $field) {
            $this->eventDispatcher->dispatch(new FieldDataEvent($field), MergeEvents::BEFORE_MERGE_FIELD);
            $this->strategy->merge($field);
            $this->eventDispatcher->dispatch(new FieldDataEvent($field), MergeEvents::AFTER_MERGE_FIELD);
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
