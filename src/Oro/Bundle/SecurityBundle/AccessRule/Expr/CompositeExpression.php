<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Composite access rule expression that have a list of expressions in it.
 */
class CompositeExpression implements ExpressionInterface
{
    public const TYPE_AND = 'AND';
    public const TYPE_OR = 'OR';
    public const TYPE_NOT = 'NOT';

    private string $type;
    /** @var ExpressionInterface[] */
    private array $expressions = [];

    public function __construct(string $type, array $expressions)
    {
        if (self::TYPE_AND !== $type && self::TYPE_OR !== $type && self::TYPE_NOT !== $type) {
            throw new \RuntimeException(\sprintf(
                'The expression type must be %s, %s or %s.',
                self::TYPE_AND,
                self::TYPE_OR,
                self::TYPE_NOT
            ));
        }
        if (\count($expressions) === 0) {
            throw new \RuntimeException('At least one child expression must exist.');
        }
        if (self::TYPE_NOT === $type && \count($expressions) !== 1) {
            throw new \RuntimeException('NOT expression must have exactly one child expression.');
        }

        $this->type = $type;
        foreach ($expressions as $expr) {
            if (!$expr instanceof ExpressionInterface) {
                throw new \RuntimeException(\sprintf(
                    'A child expression must be an instance of %s.',
                    ExpressionInterface::class
                ));
            }
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
     * Returns the composite type (AND, OR or NOT).
     */
    public function getType(): string
    {
        return $this->type;
    }

    #[\Override]
    public function visit(Visitor $visitor)
    {
        return $visitor->walkCompositeExpression($this);
    }
}
