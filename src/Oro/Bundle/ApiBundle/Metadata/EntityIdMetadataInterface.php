<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * Represents information required to manage the identifier of an entity.
 */
interface EntityIdMetadataInterface
{
    /**
     * Gets FQCN of an entity.
     *
     * @return string
     */
    public function getClassName();

    /**
     * Gets identifier field names.
     *
     * @return string[]
     */
    public function getIdentifierFieldNames();

    /**
     * Gets the name of the given property in the source entity.
     *
     * @param string $propertyName
     *
     * @return string|null The property path or NULL if the property does not exist.
     */
    public function getPropertyPath($propertyName);
}
