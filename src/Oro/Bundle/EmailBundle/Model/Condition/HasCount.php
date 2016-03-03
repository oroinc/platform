<?php

namespace Oro\Bundle\EmailBundle\Model\Condition;

use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

class HasCount extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const OP_EQUAL   = 'equal_to';
    const OP_GREATER = 'greater_than';
    const OP_LESS    = 'less_than';

    protected $countable;
    protected $operation;
    protected $value;

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $countable = $this->resolveValue($context, $this->countable);
        $count = count($countable);
        $value = $this->resolveValue($context, $this->value);

        switch ($this->operation) {
            case self::OP_EQUAL:
                return ($count == $value);
            case self::OP_GREATER:
                return ($count > $value);
            case self::OP_LESS:
                return ($count < $value);
        }

        return false;
    }

    /**
     * Returns the expression name.
     *
     * @return string
     */
    public function getName()
    {
        return 'has_count';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (count($options) !== 2) {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 2 elements, but %d given.', count($options))
            );
        }

        if (isset($options['countable'])) {
            $this->countable = $options['countable'];
        } elseif (isset($options[0])) {
            $this->countable = $options[0];
        } else {
            throw new Exception\InvalidArgumentException('Option "countable" must be set');
        }

        if (isset($options[self::OP_EQUAL])) {
            $this->operation = self::OP_EQUAL;
            $this->value = $options[self::OP_EQUAL];
        } elseif (isset($options[self::OP_GREATER])) {
            $this->operation = self::OP_GREATER;
            $this->value = $options[self::OP_GREATER];
        } elseif (isset($options[self::OP_LESS])) {
            $this->operation = self::OP_LESS;
            $this->value = $options[self::OP_LESS];
        } elseif (isset($options[1])) {
            $this->operation = self::OP_EQUAL;
            $this->value = $options[1];
        } else {
            throw new Exception\InvalidArgumentException(
                'At least one option "equal_to"/"greater_than"/"less_than" must be set'
            );
        }
    }
}
