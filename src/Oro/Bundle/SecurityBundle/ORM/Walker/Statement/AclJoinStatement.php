<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Statement;

class AclJoinStatement
{
    /**
     * @var string
     */
    protected $joinClause;

    /**
     * @var array
     */
    protected $whereConditions;

    /**
     * @param string $joinClause
     * @param array  $whereConditions
     */
    public function __construct($joinClause, array $whereConditions)
    {
        $this->joinClause       = $joinClause;
        $this->whereConditions  = $whereConditions;
    }

    /**
     * @return string
     */
    public function getJoinClause()
    {
        return $this->joinClause;
    }

    /**
     * @param string $joinClause
     *
     * @return AclJoinStatement
     */
    public function setJoinClause($joinClause)
    {
        $this->joinClause = $joinClause;
        return $this;
    }

    /**
     * @return array
     */
    public function getWhereConditions()
    {
        return $this->whereConditions;
    }

    /**
     * @param array $whereConditions
     *
     * @return AclJoinStatement
     */
    public function setWhereConditions(array $whereConditions = null)
    {
        $this->whereConditions = $whereConditions;

        return $this;
    }
}
