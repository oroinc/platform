<?php

namespace Oro\Bundle\EntityMergeBundle\Model\EntityAccessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

interface EntityAccessorInterface
{
    /**
     * Get value from entity
     *
     * @param object $entity
     * @param FieldMetadata $metadata
     * @return mixed
     */
    public function getValue($entity, FieldMetadata $metadata);

    /**
     * Set value to entity
     *
     * @param object $entity
     * @param FieldMetadata $metadata
     * @param mixed $value
     */
    public function setValue($entity, FieldMetadata $metadata, $value);

    /**
     * Get name of field accessor
     *
     * @return string
     */
    public function getName();
}
