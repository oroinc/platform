<?php

namespace Oro\Component\ConfigExpression\Condition;

use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\ExpressionInterface;

abstract class AbstractComposite extends AbstractCondition
{
    /** @var ExpressionInterface[] */
    protected $operands = [];

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!$options) {
            throw new Exception\InvalidArgumentException('Options must have at least one element.');
        }

        $this->operands = [];

        foreach ($options as $key => $operand) {
            if ($operand instanceof ExpressionInterface) {
                $this->add($operand);
            } else {
                throw new Exception\UnexpectedTypeException(
                    $operand,
                    'Oro\Component\ConfigExpression\ExpressionInterface',
                    sprintf('Invalid type of option "%s".', $key)
                );
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray($this->operands);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode($this->operands, $factoryAccessor);
    }

    /**
     * Adds an operand to the composite.
     *
     * @param ExpressionInterface $operand
     */
    public function add(ExpressionInterface $operand)
    {
        $this->operands[] = $operand;
    }
}
