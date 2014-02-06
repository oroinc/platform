<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class FieldMetadata extends Metadata implements MetadataInterface, FieldMetadataInterface
{
    /**
     * {inheritDoc}
     */
    public function getFieldName()
    {
        return $this->has(DoctrineMetadata::OPTION_NAME) ?
            $this->get(DoctrineMetadata::OPTION_NAME)->get('fieldName') : null;
    }
}
