<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\Query\Expr;

class NormalizeExpressionVisitor extends ExpressionVisitor
{
    use NormalizeFieldTrait;

    /** @var array */
    protected $placeholders;

    /**
     * @param array $placeholders
     */
    public function __construct(array $placeholders)
    {
        $this->placeholders = $placeholders;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        return new Comparison(
            $this->normalizeField($comparison->getField(), $this->placeholders),
            $comparison->getOperator(),
            $this->walkValue($comparison->getValue())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        return new CompositeExpression(
            $expr->getType(),
            array_map(
                function ($child) {
                    return $this->dispatch($child);
                },
                $expr->getExpressionList()
            )
        );
    }
}
