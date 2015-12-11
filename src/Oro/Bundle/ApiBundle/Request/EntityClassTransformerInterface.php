<?php

namespace Oro\Bundle\ApiBundle\Request;

interface EntityClassTransformerInterface
{
    /**
     * Transforms the FQCN of an entity to the type of an entity.
     *
     * @param string $entityClass The FQCN of an entity
     *
     * @return string The type of an entity
     */
    public function transform($entityClass);

    /**
     * Transforms an entity type to the FQCN of an entity.
     *
     * @param string $entityType The type of an entity
     *
     * @return mixed The FQCN of an entity
     */
    public function reverseTransform($entityType);
}
