<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Statement;

class AclJoinStorage
{
    /**
     * @var AclJoinStatement[]
     */
    protected $joinStatements = [];

    public function __construct(array $joinStatements)
    {
        $this->joinStatements = $joinStatements;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (!empty($this->joinStatements)) {
            return false;
        }

        return true;
    }

    /**
     * @param AclJoinStatement[] $joinStatements
     *
     * @return AclJoinStorage
     */
    public function setJoinStatements($joinStatements)
    {
        $this->joinStatements = $joinStatements;

        return $this;
    }

    /**
     * @return AclJoinStatement[]
     */
    public function getJoinStatements()
    {
        return $this->joinStatements;
    }
}
