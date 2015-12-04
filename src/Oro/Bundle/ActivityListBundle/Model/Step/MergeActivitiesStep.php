<?php

namespace Oro\Bundle\ActivityListBundle\Model\Step;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ActivityListBundle\Event\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use Oro\Bundle\EntityMergeBundle\Model\Step\DependentMergeStepInterface;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface;

/**
 * Class MergeActivitiesStep
 * @package Oro\Bundle\ActivityListBundle\Model\Step
 */
class MergeActivitiesStep implements DependentMergeStepInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param StrategyInterface        $strategy
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(StrategyInterface $strategy, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->strategy = $strategy;
    }

    /**
     * Merge activities
     *
     * @param EntityData $data
     */
    public function run(EntityData $data)
    {
        foreach ($data->getFields() as $field) {
            $fieldMetadata = $field->getMetadata();
            if ($fieldMetadata->has('activity') && $fieldMetadata->get('activity') === true) {
                $this->eventDispatcher->dispatch(MergeEvents::BEFORE_MERGE_ACTIVITY, new FieldDataEvent($field));
                $this->strategy->merge($field);
                $this->eventDispatcher->dispatch(MergeEvents::AFTER_MERGE_ACTIVITY, new FieldDataEvent($field));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependentSteps()
    {
        return ['Oro\\Bundle\\EntityMergeBundle\\Model\\Step\\MergeFieldsStep'];
    }
}
