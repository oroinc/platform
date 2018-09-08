<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Comparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\CompositeExpression;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Exists;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\ExpressionInterface;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\NullComparison;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Path;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Subquery;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Value;

/**
 * The base class to for classes that converts access rule expressions to more concrete expressions,
 * e.g. Doctrine AST expressions.
 */
abstract class Visitor
{
    /**
     * Converts a comparison expression into the target query language output.
     *
     * @param Comparison $comparison
     *
     * @return mixed
     */
    abstract public function walkComparison(Comparison $comparison);

    /**
     * Converts a value expression into the target query language part.
     *
     * @param Value $value
     *
     * @return mixed
     */
    abstract public function walkValue(Value $value);

    /**
     * Converts a composite expression into the target query language output.
     *
     * @param CompositeExpression $expr
     *
     * @return mixed
     */
    abstract public function walkCompositeExpression(CompositeExpression $expr);

    /**
     * Converts an access denied expression into the target query language part.
     *
     * @param AccessDenied $accessDenied
     *
     * @return mixed
     */
    abstract public function walkAccessDenied(AccessDenied $accessDenied);

    /**
     * Converts a path expression into the target query language part.
     *
     * @param Path $path
     *
     * @return mixed
     */
    abstract public function walkPath(Path $path);

    /**
     * Converts a subquery expression into the target query language output.
     *
     * @param Subquery $subquery
     *
     * @return mixed
     */
    abstract public function walkSubquery(Subquery $subquery);

    /**
     * Converts an exist expression into the target query language output.
     *
     * @param Exists $existsExpr
     *
     * @return mixed
     */
    abstract public function walkExists(Exists $existsExpr);

    /**
     * Converts a null comparison expression into the target query language output.
     *
     * @param NullComparison $comparison
     *
     * @return mixed
     */
    abstract public function walkNullComparison(NullComparison $comparison);

    /**
     * Dispatches walking an expression to the appropriate handler.
     *
     * @param ExpressionInterface $expr
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function dispatch(ExpressionInterface $expr)
    {
        switch (true) {
            case ($expr instanceof Comparison):
                return $this->walkComparison($expr);

            case ($expr instanceof Value):
                return $this->walkValue($expr);

            case ($expr instanceof CompositeExpression):
                return $this->walkCompositeExpression($expr);

            case ($expr instanceof AccessDenied):
                return $this->walkAccessDenied($expr);

            case ($expr instanceof Path):
                return $this->walkPath($expr);

            case ($expr instanceof Subquery):
                return $this->walkSubquery($expr);

            case ($expr instanceof Exists):
                return $this->walkExists($expr);

            case ($expr instanceof NullComparison):
                return $this->walkNullComparison($expr);

            default:
                throw new \RuntimeException('Unknown Expression ' . get_class($expr));
        }
    }
}
