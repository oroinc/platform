<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

abstract class AbstractComparison extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var mixed */
    protected $left;

    /** @var mixed */
    protected $right;

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (2 !== count($options)) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 2 elements, but %d given.', count($options))
            );
        }

        if (isset($options['left'])) {
            $this->left = $options['left'];
        } elseif (isset($options[0])) {
            $this->left = $options[0];
        } else {
            throw new Exception\InvalidArgumentException('Option "left" is required.');
        }

        if (isset($options['right'])) {
            $this->right = $options['right'];
        } elseif (isset($options[1])) {
            $this->right = $options[1];
        } else {
            throw new Exception\InvalidArgumentException('Option "right" is required.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ left }}'  => $this->resolveValue($context, $this->left),
            '{{ right }}' => $this->resolveValue($context, $this->right)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return $this->doCompare(
            $this->resolveValue($context, $this->left),
            $this->resolveValue($context, $this->right)
        );
    }

    /**
     * Compares two values according to logic of condition.
     *
     * @param mixed $left
     * @param mixed $right
     *
     * @return boolean
     */
    abstract protected function doCompare($left, $right);
}
