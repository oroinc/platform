<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Statement;

use Doctrine\ORM\Query\AST\Join;

class AclJoinClause
{
    /**
     * @var string
     */
    protected $abstractSchemaName;

    /**
     * @var string
     */
    protected $aliasIdentificationVariable;

    /**
     * @var string
     */
    protected $identificationVariable;

    /**
     * @var string
     */
    protected $associationField;

    /**
     * @var int
     */
    protected $joinType = Join::JOIN_TYPE_INNER;

    /**
     * @param string $abstractSchemaName
     * @param string $aliasIdentificationVariable
     * @param string $identificationVariable
     * @param string $associationField
     * @param int    $joinType
     */
    public function __construct(
        $abstractSchemaName,
        $aliasIdentificationVariable,
        $identificationVariable,
        $associationField,
        $joinType = Join::JOIN_TYPE_INNER
    )
    {
        $this->abstractSchemaName           = $abstractSchemaName;
        $this->aliasIdentificationVariable  = $aliasIdentificationVariable;
        $this->identificationVariable       = $identificationVariable;
        $this->associationField             = $associationField;
        $this->joinType                     = $joinType;
    }

    /**
     * @return string
     */
    public function getAbstractSchemaName()
    {
        return $this->abstractSchemaName;
    }

    /**
     * @param string $abstractSchemaName
     *
     * @return AclJoinClause
     */
    public function setAbstractSchemaName($abstractSchemaName)
    {
        $this->abstractSchemaName = $abstractSchemaName;
        return $this;
    }

    /**
     * @return string
     */
    public function getAliasIdentificationVariable()
    {
        return $this->aliasIdentificationVariable;
    }

    /**
     * @param string $aliasIdentificationVariable
     *
     * @return AclJoinClause
     */
    public function setAliasIdentificationVariable($aliasIdentificationVariable)
    {
        $this->aliasIdentificationVariable = $aliasIdentificationVariable;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentificationVariable()
    {
        return $this->identificationVariable;
    }

    /**
     * @param string $identificationVariable
     *
     * @return AclJoinClause
     */
    public function setIdentificationVariable($identificationVariable)
    {
        $this->identificationVariable = $identificationVariable;
        return $this;
    }

    /**
     * @return string
     */
    public function getAssociationField()
    {
        return $this->associationField;
    }

    /**
     * @param string $associationField
     *
     * @return AclJoinClause
     */
    public function setAssociationField($associationField)
    {
        $this->associationField = $associationField;
        return $this;
    }

    /**
     * @return int
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * @param int $joinType
     *
     * @return AclJoinClause
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
        return $this;
    }

    public function isAssociationJoin()
    {
        if ($this->associationField && $this->identificationVariable) {
            return true;
        }

        return false;
    }
}
