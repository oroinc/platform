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
    public function toArray()
    {
        return $this->convertToArray([$this->left, $this->right]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->left, $this->right], $factoryAccessor);
    }

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

        if (array_key_exists('left', $options)) {
            $this->left = $options['left'];
        } elseif (array_key_exists(0, $options)) {
            $this->left = $options[0];
        } else {
            throw new Exception\InvalidArgumentException('Option "left" is required.');
        }

        if (array_key_exists('right', $options)) {
            $this->right = $options['right'];
        } elseif (array_key_exists(1, $options)) {
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
            '{{ left }}'  => $this->resolveValue($context, $this->left, false),
            '{{ right }}' => $this->resolveValue($context, $this->right, false)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return $this->doCompare(
            $this->resolveValue($context, $this->left, false),
            $this->resolveValue($context, $this->right, false)
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
