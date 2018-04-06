<?php

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Provides information about parent classes for which API configuration can be applied.
 */
class ResourceHierarchyProvider
{
    /**
     * Gets parent classes for which API configuration can be applied.
     *
     * @param string $className
     *
     * @return string[]
     */
    public function getParentClassNames(string $className): array
    {
        $result = [];

        $reflection  = new \ReflectionClass($className);
        $parentClass = $reflection->getParentClass();
        while ($parentClass) {
            $parentClassName = $parentClass->getName();
            $result[] = $parentClassName;

            $reflection  = new \ReflectionClass($parentClassName);
            $parentClass = $reflection->getParentClass();
        }

        return $result;
    }
}
