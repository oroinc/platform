<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * Provides an interface for classes that can be used to get the metadata of Data API resource
 * outside of API processors.
 */
interface MetadataAccessorInterface
{
    /**
     * Gets the metadata of an entity.
     *
     * @param string $className
     *
     * @return EntityMetadata|null
     */
    public function getMetadata(string $className): ?EntityMetadata;
}
