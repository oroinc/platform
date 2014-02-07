<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

interface FieldMetadataInterface
{
    /**
     * Get name of entity field
     *
     * @return string
     */
    public function getFieldName();
}
