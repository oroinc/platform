<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * Provides an interface for classes that can be used to get the metadata of API resource
 * outside of API processors.
 */
interface MetadataAccessorInterface
{
    /**
     * Gets the metadata of an entity.
     */
    public function getMetadata(string $className): ?EntityMetadata;
}
