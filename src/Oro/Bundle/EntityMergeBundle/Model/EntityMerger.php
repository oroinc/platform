<?php

namespace Oro\Bundle\EntityMergeBundle\Model;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityMergeBundle\Event\AfterMergeEvent;
use Oro\Bundle\EntityMergeBundle\Event\BeforeMergeEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Model\FieldMerger\FieldMergerInterface;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class EntityMerger implements EntityMergerInterface
{
    /**
     * @var FieldMergerInterface
     */
    protected $fieldMerger;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param FieldMergerInterface $fieldMerger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(FieldMergerInterface $fieldMerger, EventDispatcherInterface $eventDispatcher)
    {
        $this->fieldMerger = $fieldMerger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Merge entities
     *
     * @param EntityData $data
     * @throws InvalidArgumentException
     */
    public function merge(EntityData $data)
    {
        $this->validate($data);
        $this->eventDispatcher->dispatch(MergeEvents::BEFORE_MERGE, new BeforeMergeEvent($data));
        $this->mergeFields($data);
        $this->updateDoctrineReferences($data);
        $this->eventDispatcher->dispatch(MergeEvents::AFTER_MERGE, new AfterMergeEvent($data));
    }

    /**
     * Validate merge data
     *
     * @param EntityData $data
     * @throws InvalidArgumentException If data is invalid
     */
    protected function validate(EntityData $data)
    {
        if (count($data->getEntities()) < 2) {
            // @todo Add rule to validation.yml
            throw new InvalidArgumentException('Cannot merge less than 2 entities.');
        }

        if (!$data->getMasterEntity()) {
            // @todo Add rule to validation.yml
            throw new InvalidArgumentException('Master entity must be set.');
        }
    }

    /**
     * Merge fields of entity
     *
     * @param EntityData $data
     */
    protected function mergeFields(EntityData $data)
    {
        foreach ($data->getFields() as $field) {
            $this->fieldMerger->merge($field);
        }
    }

    /**
     * Update values of Doctrine entities which are referencing to merged entities
     *
     * @param EntityData $data
     */
    protected function updateDoctrineReferences(EntityData $data)
    {
    }
}
