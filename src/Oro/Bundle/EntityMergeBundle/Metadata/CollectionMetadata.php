<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

class CollectionMetadata extends Metadata implements MetadataInterface
{
    /**
     * {inheritDoc}
     */
    public function getFieldName()
    {
        return $this->has(DoctrineMetadata::OPTION_NAME) ?
            $this->get(DoctrineMetadata::OPTION_NAME)->get('fieldName') : false;
    }
}
