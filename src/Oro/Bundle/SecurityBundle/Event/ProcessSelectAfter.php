<?php

namespace Oro\Bundle\SecurityBundle\Event;

use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\Subselect;

use Symfony\Component\EventDispatcher\Event;

class ProcessSelectAfter extends Event
{
    const NAME = 'oro_security.acl_helper.process_select.after';

    /** @var Subselect|SelectStatement */
    protected $select;

    /** @var array */
    protected $whereConditions;

    /** @var array */
    protected $joinConditions;

    /**
     * @param Subselect|SelectStatement $select
     * @param array                     $whereConditions
     * @param array                     $joinConditions
     */
    public function __construct($select, array $whereConditions, array $joinConditions)
    {
        $this->select          = $select;
        $this->whereConditions = $whereConditions;
        $this->joinConditions  = $joinConditions;
    }

    /**
     * @return Subselect|SelectStatement
     */
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @return array
     */
    public function getWhereConditions()
    {
        return $this->whereConditions;
    }

    /**
     * @return array
     */
    public function getJoinConditions()
    {
        return $this->joinConditions;
    }

    /**
     * @param SelectStatement|Subselect $select
     */
    public function setSelect($select)
    {
        $this->select = $select;
    }

    /**
     * @param array $whereConditions
     */
    public function setWhereConditions(array $whereConditions)
    {
        $this->whereConditions = $whereConditions;
    }

    /**
     * @param array $joinConditions
     */
    public function setJoinConditions(array $joinConditions)
    {
        $this->joinConditions = $joinConditions;
    }
}
