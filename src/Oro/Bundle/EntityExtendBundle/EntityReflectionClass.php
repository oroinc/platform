<?php

namespace Oro\Bundle\EntityExtendBundle;

use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\ReflectionVirtualProperty;
use Oro\Bundle\EntityExtendBundle\Doctrine\Persistence\Reflection\VirtualReflectionMethod;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use ReflectionMethod;

/**
 * Entity reflection class wrapper.
 */
class EntityReflectionClass extends \ReflectionClass
{
    public function getProperties(?int $filter = null): array
    {
        $properties = parent::getProperties($filter);
        if ($this->isNotExtendEntity()) {
            return $properties;
        }
        foreach (EntityPropertyInfo::getExtendedProperties($this->name) as $extendPropertyName) {
            $properties[] = ReflectionVirtualProperty::create($extendPropertyName);
        }

        return $properties;
    }

    public function hasProperty($name): bool
    {
        if ($this->isNotExtendEntity()) {
            return parent::hasProperty($name);
        }

        return EntityPropertyInfo::propertyExists($this->getName(), $name);
    }

    public function getProperty($name): \ReflectionProperty
    {
        if ($this->isNotExtendEntity()) {
            return parent::getProperty($name);
        }

        if ($this->hasProperty($name)) {
            if (property_exists($this->getName(), $name)) {
                return parent::getProperty($name);
            }

            return ReflectionVirtualProperty::create($name);
        }

        throw new \ReflectionException(
            sprintf(
                'Property %s::%s does not exist in extended entity.',
                $this->getName(),
                $name
            )
        );
    }

    public function isNotExtendEntity(): bool
    {
        return !is_subclass_of($this->getName(), ExtendEntityInterface::class);
    }

    public function hasMethod($name): bool
    {
        if ($this->isNotExtendEntity()) {
            return parent::hasMethod($name);
        }

        return EntityPropertyInfo::methodExists($this->getName(), $name);
    }

    public function getMethod($name): ReflectionMethod
    {
        if ($this->isNotExtendEntity()) {
            return parent::getMethod($name);
        }

        if ($this->hasMethod($name)) {
            if (method_exists($this->getName(), $name)) {
                return parent::getMethod($name);
            }

            return VirtualReflectionMethod::create($this->getName(), $name);
        }

        throw new \ReflectionException(
            sprintf(
                'method %s::%s does not exist in extended entity.',
                $this->getName(),
                $name
            )
        );
    }

    public function getMethods($filter = null): array
    {
        $methods = parent::getMethods($filter);
        if ($this->isNotExtendEntity()) {
            return $methods;
        }
        foreach (EntityPropertyInfo::getExtendedMethods($this->name) as $extendMethodName) {
            $methods[] = VirtualReflectionMethod::create($this->getName(), $extendMethodName);
        }

        return $methods;
    }
}
