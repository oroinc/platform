<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Statement;

class AclJoinStatement
{
    const ACL_SHARE_STATEMENT = 'acl_share';

    /**
     * @var AclJoinClause
     */
    protected $joinClause;

    /**
     * @var array
     */
    protected $whereConditions;

    /**
     * @var array
     */
    protected $joinConditions;

    /**
     * Name of logic which implement join statement in AclWalker
     *
     * @var string
     */
    protected $method;

    /**
     * @param AclJoinClause $joinClause
     * @param array         $whereConditions
     * @param array         $joinConditions
     * @param string        $method
     */
    public function __construct(
        AclJoinClause $joinClause,
        array $whereConditions = [],
        array $joinConditions = [],
        $method = self::ACL_SHARE_STATEMENT
    )
    {
        $this->joinClause       = $joinClause;
        $this->whereConditions  = $whereConditions;
        $this->joinConditions   = $joinConditions;
        $this->method           = $method;
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

    /**
     * @return array
     */
    public function getJoinConditions()
    {
        return $this->joinConditions;
    }

    /**
     * @param array $joinConditions
     *
     * @return AclJoinStatement
     */
    public function setJoinConditions(array $joinConditions = null)
    {
        $this->joinConditions = $joinConditions;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return AclJoinStatement
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }
}
