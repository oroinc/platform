<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Comparison access rule expression.
 */
class Comparison implements ExpressionInterface
{
    const EQ  = '=';
    const NEQ = '<>';
    const LT  = '<';
    const LTE = '<=';
    const GT  = '>';
    const GTE = '>=';
    const IN  = 'IN';
    const NIN = 'NIN';

    /** @var Path|Value */
    private $leftOperand;

    /** @var string */
    private $op;

    /** @var Path|Value */
    private $rightOperand;

    /**
     * @param mixed $leftOperand
     * @param string $operator
     * @param mixed  $rightOperand
     */
    public function __construct($leftOperand, string $operator, $rightOperand)
    {
        if (!($leftOperand instanceof ExpressionInterface)) {
            $leftOperand = new Value($leftOperand);
        }
        if (!($rightOperand instanceof ExpressionInterface)) {
            $rightOperand = new Value($rightOperand);
        }

        $this->leftOperand = $leftOperand;
        $this->op = $operator;
        $this->rightOperand = $rightOperand;
    }

    /**
     * Returns the left operand of the expression.
     *
     * @return Path|Value
     */
    public function getLeftOperand(): ExpressionInterface
    {
        return $this->leftOperand;
    }

    /**
     * Returns the right operand of the expression.
     *
     * @return Path|Value
     */
    public function getRightOperand(): ExpressionInterface
    {
        return $this->rightOperand;
    }

    /**
     * Returns comparison operator.
     *
     * @return string
     */
    public function getOperator(): string
    {
        return $this->op;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(Visitor $visitor)
    {
        return $visitor->walkComparison($this);
    }
}
