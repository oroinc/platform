<?php

namespace Oro\Component\ConfigExpression;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Config context accessor.
 */
class ContextAccessor implements ContextAccessorInterface
{
    protected ?PropertyAccessorInterface $propertyAccessor = null;

    /**
     *
     * @param object|array $context
     * @param string|PropertyPathInterface $property
     * @param mixed $value
     */
    #[\Override]
    public function setValue($context, $property, $value)
    {
        $this->getPropertyAccessor()->setValue(
            $context,
            $property,
            $this->getValue($context, $value)
        );
    }

    /**
     *
     * @param object|array $context
     * @param mixed $value
     * @return mixed
     */
    #[\Override]
    public function getValue($context, $value)
    {
        if ($value instanceof PropertyPathInterface) {
            return $this->getPropertyValue($context, $value);
        } else {
            return $value;
        }
    }

    public function getPropertyValue($context, $property)
    {
        try {
            return $this->getPropertyAccessor()->getValue($context, $property);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     *
     * @param object|array $context
     * @param mixed $value
     * @return bool
     */
    #[\Override]
    public function hasValue($context, $value)
    {
        if ($value instanceof PropertyPathInterface) {
            try {
                $key = $value->getElement($value->getLength() - 1);
                $parentValue = $value->getParent()
                    ? $this->getPropertyAccessor()->getValue($context, $value->getParent())
                    : null;
                if (is_array($parentValue)) {
                    return array_key_exists($key, $parentValue);
                } elseif ($parentValue instanceof \ArrayAccess) {
                    return isset($parentValue[$key]);
                } else {
                    return $this->getPropertyAccessor()->getValue($context, $value) !== null;
                }
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get PropertyAccessor
     *
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor()
    {
        if ($this->propertyAccessor === null) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessorWithDotSyntax();
        }

        return $this->propertyAccessor;
    }
}
