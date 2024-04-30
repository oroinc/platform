<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;

/**
 * Represents a service that provides a set of methods to get information about API resources.
 */
interface ResourceInfoProviderInterface
{
    public function getEntityClass(string $entityType): ?string;

    public function getEntityType(string $entityClass): ?string;

    public function isUntypedEntityType(string $entityType): bool;

    public function isResourceWithoutIdentifier(string $entityType): bool;

    public function isCollectionSubresource(string $entityType, string $associationName): bool;

    public function getSubresourceTargetEntityType(string $entityType, string $associationName): ?string;
}
