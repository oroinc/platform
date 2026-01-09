<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Defines the contract for accessing and manipulating values within an evaluation context.
 *
 * The context accessor provides a unified interface for getting, setting, and checking the
 * existence of values in a context object. It supports both direct property access and
 * property path expressions, enabling flexible data access patterns in expressions.
 */
interface ContextAccessorInterface
{
    /**
     * Sets the value to the context.
     *
     * @param mixed $context
     * @param string|PropertyPathInterface $property
     * @param mixed $value
     */
    public function setValue($context, $property, $value);

    /**
     * Gets the value from the context.
     *
     * @param mixed $context
     * @param string|PropertyPathInterface $property
     *
     * @return mixed
     */
    public function getValue($context, $property);

    /**
     * Checks whether the context has the value.
     *
     * @param mixed $context
     * @param string|PropertyPathInterface $property
     *
     * @return bool
     */
    public function hasValue($context, $property);
}
