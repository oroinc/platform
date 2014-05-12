<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

interface AccessorInterface
{
    /**
     * Get value from entity
     *
     * @param object        $entity
     * @param FieldMetadata $metadata
     * @return mixed
     */
    public function getValue($entity, FieldMetadata $metadata);

    /**
     * Set value to entity
     *
     * @param object        $entity
     * @param FieldMetadata $metadata
     * @param mixed         $value
     */
    public function setValue($entity, FieldMetadata $metadata, $value);

    /**
     * Checks if this class supports accessing entity
     *
     * @param string        $entity
     * @param FieldMetadata $metadata
     * @return bool
     */
    public function supports($entity, FieldMetadata $metadata);

    /**
     * Get name of field accessor
     *
     * @return string
     */
    public function getName();
}
