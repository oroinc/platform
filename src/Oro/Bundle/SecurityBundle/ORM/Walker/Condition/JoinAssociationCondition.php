<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Condition;


use Doctrine\ORM\Query\AST\PathExpression;

class JoinAssociationCondition extends JoinAclCondition
{
    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @var array
     */
    protected $joinConditions;

    /**
     * @param string $entityAlias
     * @param string $entityField
     * @param mixed  $value
     * @param int    $pathExpressionType
     * @param string $entityClass
     * @param array  $joinConditions
     */
    public function __construct(
        $entityAlias,
        $entityField = null,
        $value = null,
        $pathExpressionType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
        $entityClass = null,
        $joinConditions = null
    ) {
        $this->entityClass    = $entityClass;
        $this->joinConditions = $joinConditions;

        parent::__construct($entityAlias, $entityField, $value, $pathExpressionType);
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param array $joinConditions
     */
    public function setJoinConditions($joinConditions)
    {
        $this->joinConditions = $joinConditions;
    }

    /**
     * @return array
     */
    public function getJoinConditions()
    {
        return $this->joinConditions;
    }
}
