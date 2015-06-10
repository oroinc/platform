<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

interface ContextAccessorInterface
{
    /**
     * Sets the value to the context.
     *
     * @param mixed                 $context
     * @param PropertyPathInterface $property
     * @param mixed                 $value
     */
    public function setValue($context, PropertyPathInterface $property, $value);

    /**
     * Gets the value from the context.
     *
     * @param mixed                 $context
     * @param PropertyPathInterface $property
     *
     * @return mixed
     */
    public function getValue($context, PropertyPathInterface $property);

    /**
     * Checks whether the context has the value.
     *
     * @param mixed                 $context
     * @param PropertyPathInterface $property
     *
     * @return bool
     */
    public function hasValue($context, PropertyPathInterface $property);
}
