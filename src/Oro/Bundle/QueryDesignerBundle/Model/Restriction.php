<?php

namespace Oro\Bundle\QueryDesignerBundle\Model;

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
