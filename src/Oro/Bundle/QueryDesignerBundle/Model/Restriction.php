<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

/**
 * Represents a single filter restriction in a query designer filter expression.
 *
 * This class encapsulates a leaf node in the filter expression tree, containing
 * an actual filter expression (typically a Doctrine expression), the logical operator
 * (`AND`/`OR`) that connects it to other restrictions, and a flag indicating whether
 * the restriction is computed (derived from aggregated data) or uncomputed (from base data).
 * Restrictions are the atomic building blocks of filter expressions, combined through
 * GroupNode instances to form complex filter logic.
 */
class Restriction
{
    /** @var mixed Expr */
    protected $restriction;

    /** @var string */
    protected $condition;

    /** @var bool */
    protected $computed;

    /**
     * @param mixed $restriction Expr
     * @param string $condition
     * @param bool $computed
     */
    public function __construct($restriction, $condition, $computed)
    {
        $this->restriction = $restriction;
        $this->condition = $condition;
        $this->computed = $computed;
    }

    /**
     * @return mixed Expr
     */
    public function getRestriction()
    {
        return $this->restriction;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return bool
     */
    public function isComputed()
    {
        return $this->computed;
    }
}
