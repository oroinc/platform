<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides information about parent classes for which API configuration can be applied.
 */
class ResourceHierarchyProvider
{
    /**
     * Gets parent classes for which API configuration can be applied for the given class.
     *
     * @param string $className
     *
     * @return string[]
     */
    public function getParentClassNames($className)
    {
        $result = [];

        $reflection  = new \ReflectionClass($className);
        $parentClass = $reflection->getParentClass();
        while ($parentClass) {
            $parentClassName = $parentClass->getName();
            // do not add an extended entity proxy to the list of parent classes
            if (strpos($parentClassName, ExtendHelper::ENTITY_NAMESPACE) !== 0) {
                $result[] = $parentClassName;
            }

            $reflection  = new \ReflectionClass($parentClassName);
            $parentClass = $reflection->getParentClass();
        }

        return $result;
    }
}
