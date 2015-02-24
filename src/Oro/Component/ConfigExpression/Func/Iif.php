<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Implements the ternary operator '?:'.
 * This class supports both syntax the full (expr ? true_value : false_value)
 * and the short (expr ?: false_value).
 */
class Iif extends AbstractFunction
{
    /** @var ExpressionInterface */
    protected $expression;

    /** @var mixed */
    protected $trueValue;

    /** @var mixed */
    protected $falseValue;

    /** @var boolean */
    protected $isShort;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'iif';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->expression, $this->trueValue, $this->falseValue]);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $count = count($options);
        if ($count < 2 || $count > 3) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 2 or 3 elements, but %d given.', count($options))
            );
        }

        $this->expression = reset($options);
        next($options);
        if ($count === 2) {
            // short syntax
            $this->isShort    = true;
            $this->falseValue = current($options);
        } else {
            // full syntax
            $this->isShort   = false;
            $this->trueValue = current($options);
            next($options);
            $this->falseValue = current($options);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        $expression = $this->resolveValue($context, $this->expression);
        if ($this->isShort) {
            return $expression ?: $this->resolveValue($context, $this->falseValue);
        } else {
            return $expression
                ? $this->resolveValue($context, $this->trueValue)
                : $this->resolveValue($context, $this->falseValue);
        }
    }
}
