<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Represents IS NULL or IS NOT NULL comparison expression.
 */
class NullComparison implements ExpressionInterface
{
    /** @var bool */
    private $not;

    /** @var Path */
    private $expression;

    /**
     * @param Path $expression
     * @param bool $not
     */
    public function __construct(Path $expression, bool $not = false)
    {
        $this->expression = $expression;
        $this->not = $not;
    }

    /**
     * @return bool
     */
    public function isNot(): bool
    {
        return $this->not;
    }

    /**
     * @return Path
     */
    public function getExpression(): Path
    {
        return $this->expression;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(Visitor $visitor)
    {
        return $visitor->walkNullComparison($this);
    }
}
