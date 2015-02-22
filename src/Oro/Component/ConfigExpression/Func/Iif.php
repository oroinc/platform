<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Implements the ternary operator '?:'.
 */
class Iif extends AbstractFunction
{
    /** @var ExpressionInterface */
    protected $expression;

    /** @var mixed */
    protected $trueValue;

    /** @var mixed */
    protected $falseValue;

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
        if (count($options) !== 3) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 3 elements, but %d given.', count($options))
            );
        }

        $this->expression = reset($options);
        if (!$this->expression instanceof ExpressionInterface) {
            throw new Exception\UnexpectedTypeException(
                $this->expression,
                'Oro\Component\ConfigExpression\ExpressionInterface',
                'Invalid expression type.'
            );
        }

        next($options);
        $this->trueValue = current($options);
        next($options);
        $this->falseValue = current($options);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        return $this->expression->evaluate($context, $this->errors)
            ? $this->resolveValue($context, $this->trueValue)
            : $this->resolveValue($context, $this->falseValue);
    }
}
