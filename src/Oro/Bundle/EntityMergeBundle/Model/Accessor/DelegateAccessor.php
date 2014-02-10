<?php

namespace Oro\Bundle\EntityMergeBundle\Model\Accessor;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

/**
 * TODO: This class should have all accessors and delegate calls to them according to FieldMetadata
 */
class DelegateAccessor implements AccessorInterface
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
