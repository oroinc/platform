<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class FieldMetadata extends Metadata implements FieldMetadataInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFieldName()
    {
        if (!$this->getDoctrineMetadata()->has('fieldName')) {
            throw new InvalidArgumentException('Field name not set');
        }

        return $this->getDoctrineMetadata()->get('fieldName');
    }
}
