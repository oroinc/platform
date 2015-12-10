<?php

namespace Oro\Bundle\EntityBundle\Provider;

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
