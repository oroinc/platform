<?php

namespace Oro\Bundle\SecurityBundle\AccessRule\Expr;

use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Visitor;

/**
 * Subquery access rule expression.
 */
class Subquery implements ExpressionInterface
{
    /** @var string */
    private $from;

    /** @var string */
    private $alias;

    /** @var Criteria */
    private $criteria;

    public function __construct(string $from, string $alias, Criteria $criteria)
    {
        $this->from = $from;
        $this->alias = $alias;
        $this->criteria = $criteria;
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function visit(Visitor $visitor)
    {
        return $visitor->walkSubquery($this);
    }
}
