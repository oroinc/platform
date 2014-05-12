<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Condition;

use Doctrine\ORM\Query\AST\PathExpression;

class AclCondition
{
    /**
     * @var string
     */
    protected $entityAlias;

    /**
     * @var string
     */
    protected $entityField;

    /**
     * @var int[]
     */
    protected $value;

    /**
     * @var int
     */
    protected $pathExpressionType;

    /**
     * @param string $entityAlias
     * @param string $entityField
     * @param mixed  $value
     * @param int    $pathExpressionType
     */
    public function __construct(
        $entityAlias,
        $entityField = null,
        $value = null,
        $pathExpressionType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
    ) {
        $this->entityAlias        = $entityAlias;
        $this->entityField        = $entityField;
        $this->value              = $value;
        $this->pathExpressionType = $pathExpressionType;
    }

    /**
     * @param string $entityAlias
     */
    public function setEntityAlias($entityAlias)
    {
        $this->entityAlias = $entityAlias;
    }

    /**
     * @return string
     */
    public function getEntityAlias()
    {
        return $this->entityAlias;
    }

    /**
     * @param string $entityField
     */
    public function setEntityField($entityField)
    {
        $this->entityField = $entityField;
    }

    /**
     * @return string
     */
    public function getEntityField()
    {
        return $this->entityField;
    }

    /**
     * @param int[] $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return int[]
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param int $pathExpressionType
     */
    public function setPathExpressionType($pathExpressionType)
    {
        $this->pathExpressionType = $pathExpressionType;
    }

    /**
     * @return int
     */
    public function getPathExpressionType()
    {
        return $this->pathExpressionType;
    }
}
