<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Entity field path expression.
 */
class Path implements ExpressionInterface
{
    /** @var string */
    private $alias;

    /** @var string */
    private $field;

    /**
     * @param string $field
     * @param string $alias
     */
    public function __construct(string $field, string $alias = null)
    {
        $this->alias = $alias;
        $this->field = $field;
    }

    /**
     * Sets the object alias.
     *
     * @param string $alias
     */
    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    /**
     * Returns the alias of the object in the query.
     *
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Returns the object field name.
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function visit(Visitor $visitor)
    {
        return $visitor->walkPath($this);
    }
}
