<?php

namespace Oro\Component\ConfigExpression;

use Oro\Component\ConfigExpression\PropertyAccess\PropertyPathInterface;

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
     *
     * @return mixed
     */
    protected function resolveValue($context, $value)
    {
        if ($value instanceof PropertyPathInterface) {
            return $this->contextAccessor->getValue($context, $value);
        } elseif ($value instanceof ExpressionInterface) {
            return $value->evaluate($context, $this->errors);
        }

        return $value;
    }
}
