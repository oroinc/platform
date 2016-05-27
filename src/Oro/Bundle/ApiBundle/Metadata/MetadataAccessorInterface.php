<?php

namespace Oro\Bundle\ApiBundle\Metadata;

interface MetadataAccessorInterface
{
    /**
     * Gets metadata of an entity.
     *
     * @param string $className
     *
     * @return EntityMetadata|null
     */
    public function getMetadata($className);
}
