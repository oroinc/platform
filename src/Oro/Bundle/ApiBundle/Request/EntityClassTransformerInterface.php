<?php

namespace Oro\Bundle\ApiBundle\Request;

interface EntityClassTransformerInterface
{
    /**
     * Transforms the FQCN of an entity to the type of an entity.
     *
     * @param string $entityClass    The FQCN of an entity
     * @param bool   $throwException Whether to throw exception in case the the transformation failed
     *
     * @return string|null The type of an entity
     */
    public function transform($entityClass, $throwException = true);

    /**
     * Transforms an entity type to the FQCN of an entity.
     *
     * @param string $entityType     The type of an entity
     * @param bool   $throwException Whether to throw exception in case the the transformation failed
     *
     * @return mixed|null The FQCN of an entity
     */
    public function reverseTransform($entityType, $throwException = true);
}
