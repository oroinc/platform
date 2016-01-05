<?php

namespace Oro\Bundle\ApiBundle\Request;

interface EntityIdTransformerInterface
{
    /**
     * Transforms an entity identifier to a string representation.
     *
     * A combined entity identifier should be an array in the following format:
     * [field => value, ...]
     *
     * @param mixed $id The identifier of an entity
     *
     * @return string A string representation of entity identifier
     */
    public function transform($id);

    /**
     * Transforms a string representation of an entity identifier to its original representation.
     *
     * A combined entity identifier is returned as an array in the following format:
     * [field => value, ...]
     *
     * @param string $entityClass The FQCN of an entity
     * @param string $value       A string representation of entity identifier
     *
     * @return mixed The identifier of an entity
     */
    public function reverseTransform($entityClass, $value);
}
