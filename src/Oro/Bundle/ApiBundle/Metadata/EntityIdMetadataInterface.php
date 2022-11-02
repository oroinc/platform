<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * Represents information required to manage the identifier of an entity.
 */
interface EntityIdMetadataInterface
{
    /**
     * Gets FQCN of an entity.
     */
    public function getClassName(): string;

    /**
     * Gets identifier field names.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames(): array;

    /**
     * Gets the path of the given property in the source entity.
     * Returns NULL if the property does not exist.
     */
    public function getPropertyPath(string $propertyName): ?string;
}
