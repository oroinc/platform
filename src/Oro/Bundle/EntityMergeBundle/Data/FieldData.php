<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

/**
 * Encapsulates merge data for a single field within an entity merge operation.
 *
 * This class manages field-level merge information including the field's metadata,
 * the source entity from which the field value will be taken, and the merge mode
 * (e.g., `REPLACE` or `UNITE`) that determines how the field value is merged into the
 * master entity. It provides access to the parent EntityData and field metadata.
 */
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

    public function __construct(EntityData $entityData, FieldMetadata $metadata)
    {
        $this->entityData = $entityData;
        $this->metadata = $metadata;
        if ($metadata->getMergeMode()) {
            $this->mode = $metadata->getMergeMode();
        }
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
     */
    public function setSourceEntity($entity)
    {
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
