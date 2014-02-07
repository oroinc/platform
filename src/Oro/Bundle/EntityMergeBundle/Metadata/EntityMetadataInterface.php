<?php

namespace Oro\Bundle\EntityMergeBundle\Metadata;

interface EntityMetadataInterface extends MetadataInterface
{
    /**
     * Get class name of entity
     *
     * @return string
     */
    public function getClassName();
}
