<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Composite access rule expression that have a list of expressions in it.
 */
class CompositeExpression implements ExpressionInterface
{
    const TYPE_AND = 'AND';
    const TYPE_OR = 'OR';

    /** @var string */
    private $type;

    /** @var ExpressionInterface[]  */
    private $expressions = [];

    /**
     * @param string $type
     * @param array  $expressions
     */
    public function __construct(string $type, array $expressions)
    {
        $this->type = $type;

        foreach ($expressions as $expr) {
            $this->expressions[] = $expr;
        }
    }

    /**
     * Returns the list of expressions nested in this composite.
     *
     * @return ExpressionInterface[]
     */
    public function getExpressionList(): array
    {
        return $this->expressions;
    }

    /**
     * Returns the composite type (AND or OR).
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(Visitor $visitor)
    {
        return $visitor->walkCompositeExpression($this);
    }
}
