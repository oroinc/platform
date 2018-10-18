<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * Provides an interface for classes that transform entity identifier value
 * to and from a representation suitable for a specific request.
 */
interface EntityIdTransformerInterface
{
    /**
     * Transforms an entity identifier to a request specific representation.
     *
     * A composite entity identifier should be an array in the following format:
     * [field => value, ...]
     *
     * @param mixed          $id       The identifier of an entity
     * @param EntityMetadata $metadata The metadata of an entity
     *
     * @return mixed A request specific representation of entity identifier
     */
    public function transform($id, EntityMetadata $metadata);

    /**
     * Transforms a request specific representation of an entity identifier to its original representation.
     *
     * A composite entity identifier is returned as an array in the following format:
     * [field => value, ...]
     *
     * @param mixed          $value    A request specific representation of entity identifier
     * @param EntityMetadata $metadata The metadata of an entity
     *
     * @return mixed The identifier of an entity
     */
    public function reverseTransform($value, EntityMetadata $metadata);
}
