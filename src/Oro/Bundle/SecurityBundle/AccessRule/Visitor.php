<?php

namespace Oro\Bundle\SecurityBundle\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\Expr\AccessDenied;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
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
     */
    abstract public function walkComparison(Comparison $comparison): mixed;

    /**
     * Converts a value expression into the target query language part.
     */
    abstract public function walkValue(Value $value): mixed;

    /**
     * Converts a composite expression into the target query language output.
     */
    abstract public function walkCompositeExpression(CompositeExpression $expr): mixed;

    /**
     * Converts an access denied expression into the target query language part.
     */
    abstract public function walkAccessDenied(AccessDenied $accessDenied): mixed;

    /**
     * Converts a path expression into the target query language part.
     */
    abstract public function walkPath(Path $path): mixed;

    /**
     * Converts a subquery expression into the target query language output.
     */
    abstract public function walkSubquery(Subquery $subquery): mixed;

    /**
     * Converts an exist expression into the target query language output.
     */
    abstract public function walkExists(Exists $existsExpr): mixed;

    /**
     * Converts a null comparison expression into the target query language output.
     */
    abstract public function walkNullComparison(NullComparison $comparison): mixed;

    /**
     * Converts an association expression into the target query language output.
     */
    abstract public function walkAssociation(Association $association): mixed;

    /**
     * Dispatches walking an expression to the appropriate handler.
     *
     * @throws \RuntimeException if an expression cannot be built
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function dispatch(ExpressionInterface $expr): mixed
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
            case ($expr instanceof Association):
                return $this->walkAssociation($expr);
            default:
                throw new \RuntimeException('Unknown Expression ' . \get_class($expr));
        }
    }
}
