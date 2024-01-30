<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Exists;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Subquery;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Value;

/**
 * The base class for classes that make some modifications of access rule expression.
 */
abstract class ModifyExpressionVisitor extends Visitor
{
    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison): mixed
    {
        return new Comparison(
            $comparison->getLeftOperand()->visit($this),
            $comparison->getOperator(),
            $comparison->getRightOperand()->visit($this)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr): mixed
    {
        $modifiedExpressions = [];
        foreach ($expr->getExpressionList() as $expression) {
            $modifiedExpression = $expression->visit($this);
            if (null !== $modifiedExpression) {
                $modifiedExpressions[] = $modifiedExpression;
            }
        }

        if (!$modifiedExpressions) {
            return null;
        }
        if (\count($modifiedExpressions) === 1) {
            return $modifiedExpressions[0];
        }

        return new CompositeExpression($expr->getType(), $modifiedExpressions);
    }

    /**
     * {@inheritDoc}
     */
    public function walkAccessDenied(AccessDenied $accessDenied): mixed
    {
        return $accessDenied;
    }

    /**
     * {@inheritDoc}
     */
    public function walkPath(Path $path): mixed
    {
        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function walkSubquery(Subquery $subquery): mixed
    {
        return $subquery;
    }

    /**
     * {@inheritDoc}
     */
    public function walkExists(Exists $existsExpr): mixed
    {
        return new Exists(
            $existsExpr->getExpression()->visit($this),
            $existsExpr->isNot()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function walkNullComparison(NullComparison $comparison): mixed
    {
        return new NullComparison(
            $comparison->getExpression()->visit($this),
            $comparison->isNot()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function walkAssociation(Association $association): mixed
    {
        return $association;
    }
}
