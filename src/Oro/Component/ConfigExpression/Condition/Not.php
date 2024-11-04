<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ExpressionInterface;

/**
 * Implements logical NOT operator.
 */
class Not extends AbstractCondition
{
    /** @var ExpressionInterface */
    protected $operand;

    #[\Override]
    public function getName()
    {
        return 'not';
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray($this->operand);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->operand, $factoryAccessor);
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (1 === count($options)) {
            $operand = reset($options);
            if ($operand instanceof ExpressionInterface) {
                $this->operand = $operand;
            } else {
                throw new Exception\UnexpectedTypeException(
                    $operand,
                    'Oro\Component\ConfigExpression\ExpressionInterface',
                    'Invalid option type.'
                );
            }
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf('Options must have 1 element, but %d given.', count($options))
            );
        }

        return $this;
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        return !$this->operand->evaluate($context, $this->errors);
    }
}
