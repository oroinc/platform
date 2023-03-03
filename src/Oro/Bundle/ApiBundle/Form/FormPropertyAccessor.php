<?php

namespace Oro\Bundle\ApiBundle\Form;

use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * This property accessor allows to set NULL to a property value
 * even if it is not acceptable by a setter.
 * It is required to avoid exceptions like 'Expected argument of type "SomeType",
 * "NULL" given at property path "property"' during submitting API form.
 * If NULL is not allowed for a property, the NotNull or NotBlank validation constraint
 * must be configured for this property.
 */
class FormPropertyAccessor implements PropertyAccessorInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        try {
            $this->propertyAccessor->setValue($objectOrArray, $propertyPath, $value);
        } catch (InvalidArgumentException $e) {
            if (null !== $value || !\is_object($objectOrArray)) {
                throw $e;
            }
            $propertyName = $this->getPropertyName($propertyPath);
            if (!$propertyName || !$this->trySetValueViaReflection($objectOrArray, $propertyPath, $value)) {
                throw $e;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        return $this->propertyAccessor->getValue($objectOrArray, $propertyPath);
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable($objectOrArray, $propertyPath)
    {
        return $this->propertyAccessor->isWritable($objectOrArray, $propertyPath);
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable($objectOrArray, $propertyPath)
    {
        return $this->propertyAccessor->isReadable($objectOrArray, $propertyPath);
    }

    private function getPropertyName(string|PropertyPathInterface $propertyPath): ?string
    {
        $path = null;
        if (\is_string($propertyPath)) {
            $path = $propertyPath;
        } elseif ($propertyPath instanceof PropertyPathInterface && $propertyPath->getLength() === 1) {
            $path = $propertyPath->getElement(0);
        }

        if (!\is_string($path) || str_contains($path, '.')) {
            $path = null;
        }

        return $path;
    }

    private function trySetValueViaReflection(object $object, string $propertyName, mixed $value): bool
    {
        $refl = new EntityReflectionClass($object);
        if (!$refl->hasProperty($propertyName)) {
            return false;
        }

        $property = $refl->getProperty($propertyName);
        if (!$property->isPublic()) {
            $property->setAccessible(true);
        }
        $property->setValue($object, $value);

        return true;
    }
}
