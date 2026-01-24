<?php

namespace Oro\Bundle\EntityBundle\Provider;

/**
 * Defines the contract for providing entity inheritance hierarchies.
 *
 * Implementations of this interface provide information about entity inheritance
 * relationships, including parent entities and mapped superclasses for each entity.
 */
interface EntityHierarchyProviderInterface
{
    /**
     * Gets the hierarchy for all entities who has at least one parent entity/mapped superclass
     *
     * @return array
     */
    public function getHierarchy();

    /**
     * Gets parent entities/mapped superclasses for the given entity
     *
     * @param string $className
     *
     * @return array
     */
    public function getHierarchyForClassName($className);
}
