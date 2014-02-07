<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class FieldMetadata extends Metadata implements FieldMetadataInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFieldName()
    {
        /** @todo Some checks that $this->get(DoctrineMetadata::OPTION_NAME) returns valid value? */
        return $this->has(DoctrineMetadata::OPTION_NAME) ?
            $this->get(DoctrineMetadata::OPTION_NAME)->get('fieldName') : null;
    }
}
