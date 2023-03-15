<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Mapping;

use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty;

/**
 * PHP Runtime Reflection Service with support of virtual properties
 */
class VirtualPropertyRuntimeReflectionService extends RuntimeReflectionService
{
    /**
     * @inheritDoc
     */
    public function getAccessibleProperty($class, $property)
    {
        if (property_exists($class, $property)) {
            return parent::getAccessibleProperty($class, $property);
        }

        return ReflectionVirtualProperty::create($property);
    }

    /**
     * @inheritDoc
     */
    public function getParentClasses($class)
    {
        if (!class_exists($class)) {
            return [];
        }
        $parents = class_parents($class);
        assert($parents !== false);

        return $parents;
    }
}
