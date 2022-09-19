<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Comparison access rule expression.
 */
class Comparison implements ExpressionInterface
{
    public const EQ  = '=';
    public const NEQ = '<>';
    public const LT  = '<';
    public const LTE = '<=';
    public const GT  = '>';
    public const GTE = '>=';
    /** Checks that the left operand matches any value in the list from the right operand. */
    public const IN  = 'IN';
    /** Checks that the left operand does not match any value in the list from the right operand. */
    public const NIN = 'NIN';
    /**
     * For string fields, checks that the left operand contains a substring from the right operand.
     * For array fields, checks that any value in the list from the left operand is matched any value
     * in the list from the right operand.
     */
    public const CONTAINS = 'CONTAINS';

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
