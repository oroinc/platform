<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class FieldData
{
    /**
     * @var EntityData
     */
    protected $entityData;

    /**
     * @var FieldMetadata
     */
    protected $metadata;

    /**
     * @var object
     */
    protected $sourceEntity;

    /**
     * @var string
     */
    protected $mode = MergeModes::REPLACE;

    /**
     * @param EntityData $entityData
     * @param FieldMetadata $metadata
     */
    public function __construct(EntityData $entityData, FieldMetadata $metadata)
    {
        $this->entityData = $entityData;
        $this->metadata = $metadata;
    }

    /**
     * Get entity merge data
     *
     * @return EntityData
     */
    public function getEntityData()
    {
        return $this->entityData;
    }

    /**
     * Get merge metadata
     *
     * @return FieldMetadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Get field name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->getMetadata()->getFieldName();
    }

    /**
     * Set source entity
     *
     * @param object $entity
     * @return FieldData
     * @throws InvalidArgumentException If $entity is not an object
     */
    public function setSourceEntity($entity)
    {
        if (!$this->entityData->hasEntity($entity)) {
            throw new InvalidArgumentException('Passed entity must be included to merge data.');
        }
        $this->sourceEntity = $entity;

        return $this;
    }

    /**
     * Get source entity
     *
     * @return object
     */
    public function getSourceEntity()
    {
        return $this->sourceEntity;
    }

    /**
     * Set field merge mode
     *
     * @param $mode
     * @return FieldData
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }
}
