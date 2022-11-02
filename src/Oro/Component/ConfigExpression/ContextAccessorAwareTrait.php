<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Provides methods to resolve the property path or expression in the context
 *
 * @property \ArrayAccess|null $errors
 */
trait ContextAccessorAwareTrait
{
    /** @var ContextAccessorInterface */
    protected $contextAccessor;

    /**
     * Sets the context accessor.
     */
    public function setContextAccessor(ContextAccessorInterface $contextAccessor)
    {
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * Returns the resolved value.
     *
     * @param mixed $context
     * @param mixed $value
     * @param mixed $strict
     *
     * @return mixed
     */
    protected function resolveValue($context, $value, $strict = true)
    {
        if ($value instanceof PropertyPathInterface) {
            if ($strict || $this->contextAccessor->hasValue($context, $value)) {
                return $this->contextAccessor->getValue($context, $value);
            }

            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value->evaluate($context, $this->errors);
        }

        return $value;
    }
}
