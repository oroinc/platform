<?php

namespace Oro\Component\ConfigExpression;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @property \ArrayAccess|null errors
 */
trait ContextAccessorAwareTrait
{
    /** @var ContextAccessorInterface */
    protected $contextAccessor;

    /**
     * Sets the context accessor.
     *
     * @param ContextAccessorInterface $contextAccessor
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
            return $strict || $this->contextAccessor->hasValue($context, $value)
                ? $this->contextAccessor->getValue($context, $value)
                : null;
        } elseif ($value instanceof ExpressionInterface) {
            return $value->evaluate($context, $this->errors);
        }

        return $value;
    }
}
