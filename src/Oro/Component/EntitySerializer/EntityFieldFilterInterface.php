<?php

namespace Oro\Component\EntitySerializer;

/**
 * Represents a service that is used to check whether a specific entity field
 * is applicable to process by the entity serializer component.
 */
interface EntityFieldFilterInterface
{
    public function isApplicableField(string $className, string $fieldName): bool;
}
