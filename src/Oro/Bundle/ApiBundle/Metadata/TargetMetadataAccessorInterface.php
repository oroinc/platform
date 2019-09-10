<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * Represents an accessor to target metadata by a specified target class name and association path.
 * It is used for multi-target associations.
 * @see \Oro\Bundle\ApiBundle\Model\EntityIdentifier
 */
interface TargetMetadataAccessorInterface
{
    /**
     * Gets metadata for the given target class name and association path.
     *
     * @param string      $targetClassName The class name of the target entity
     * @param string|null $associationPath The path from a root entity to the association.
     *
     * @return EntityMetadata|null
     */
    public function getTargetMetadata(string $targetClassName, ?string $associationPath): ?EntityMetadata;
}
