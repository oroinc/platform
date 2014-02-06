<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class FieldData
{
    /**
     * @var FieldMetadata
     */
    protected $metadata;

    /**
     * @var object
     */
    protected $sourceEntity;

    /**
     * @param FieldMetadata $metadata
     */
    public function __construct(FieldMetadata $metadata)
    {
        $this->metadata = $metadata;
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
     * Set source entity
     *
     * @param object $entity
     * @return FieldData
     * @throws InvalidArgumentException If $entity is not an object
     */
    public function setSourceEntity($entity)
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException(sprintf('$entity should be an object, %s given.', gettype($entity)));
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
}
