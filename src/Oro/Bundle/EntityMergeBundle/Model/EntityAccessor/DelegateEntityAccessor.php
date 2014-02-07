<?php

namespace Oro\Bundle\EntityMergeBundle\Model\EntityAccessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

/**
 * TODO: This class should have all accessors and delegate calls to them according to FieldMetadata
 */
class DelegateEntityAccessor implements EntityAccessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, FieldMetadata $metadata)
    {
        // TODO: Implement getValue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, FieldMetadata $metadata, $value)
    {
        // TODO: Implement setValue() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'delegate';
    }
}
