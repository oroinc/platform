<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

use Oro\Component\PropertyAccess\PropertyAccessor;

class ContextAccessor implements ContextAccessorInterface
{
    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * {@inheritdoc}
     */
    public function setValue($context, PropertyPathInterface $property, $value)
    {
        $this->getPropertyAccessor()->setValue($context, $property, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($context, PropertyPathInterface $property)
    {
        return $this->getPropertyAccessor()->getValue($context, $property);
    }

    /**
     * {@inheritdoc}
     */
    public function hasValue($context, PropertyPathInterface $property)
    {
        try {
            $this->getPropertyAccessor()->getValue($context, $property);
        } catch (NoSuchPropertyException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if ($this->propertyAccessor === null) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
