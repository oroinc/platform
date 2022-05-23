<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Represents a service that provides an information whether an access check to associations should be ignored.
 */
interface AssociationAccessExclusionProviderInterface
{
    public function isIgnoreAssociationAccessCheck(string $entityClass, string $associationName): bool;
}
