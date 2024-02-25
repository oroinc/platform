<?php

namespace Oro\Component\PhpUtils\Attribute\Reader;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Inspired by Doctrine`s AttributeReader
 *
 * @see \Doctrine\ORM\Mapping\Driver\AttributeReader
 */
class AttributeReader
{
    public function getClassAttribute(ReflectionClass $class, string $attributeName): mixed
    {
        return $this->getClassAttributes($class)[$attributeName] ?? null;
    }

    public function getPropertyAttribute(ReflectionProperty $property, string $attributeName): mixed
    {
        return $this->getPropertyAttributes($property)[$attributeName] ?? null;
    }

    public function getMethodAttribute(ReflectionMethod $method, string $attributeName): mixed
    {
        return $this->getMethodAttributes($method)[$attributeName] ?? null;
    }

    public function getClassAttributes(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    private function getPropertyAttributes(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    public function getMethodAttributes(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            $instances[$attributeName] = $attribute->newInstance();
        }

        return $instances;
    }
}
