<?php

namespace Oro\Bundle\ApiBundle\Form;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * This property accessor allows to set NULL to a property value
 * even if it is not acceptable by a setter.
 * This is required to avoid exceptions like "Argument 1 passed to Class::setProperty()
 * must be an instance of Class, null given." during submitting Data API form.
 * If NULL is not allowed for a property, the NotNull or NotBlank validation constraint
 * must be configured for this property.
 */
class FormPropertyAccessor implements PropertyAccessorInterface
{
    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(&$objectOrArray, $propertyPath, $value)
    {
        try {
            $this->propertyAccessor->setValue($objectOrArray, $propertyPath, $value);
        } catch (\TypeError $e) {
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
     * {@inheritdoc}
     */
    public function getValue($objectOrArray, $propertyPath)
    {
        return $this->propertyAccessor->getValue($objectOrArray, $propertyPath);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($objectOrArray, $propertyPath)
    {
        return $this->propertyAccessor->isWritable($objectOrArray, $propertyPath);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($objectOrArray, $propertyPath)
    {
        return $this->propertyAccessor->isReadable($objectOrArray, $propertyPath);
    }

    /**
     * @param string|PropertyPathInterface $propertyPath
     *
     * @return string|null
     */
    private function getPropertyName($propertyPath)
    {
        $path = null;
        if (\is_string($propertyPath)) {
            $path = $propertyPath;
        } elseif ($propertyPath instanceof PropertyPathInterface && $propertyPath->getLength() === 1) {
            $path = $propertyPath->getElement(0);
        }

        if (!\is_string($path) || false !== \strpos($path, '.')) {
            $path = null;
        }

        return $path;
    }

    /**
     * @param object $object
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return bool
     */
    private function trySetValueViaReflection($object, $propertyName, $value)
    {
        $refl = new \ReflectionClass($object);
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
