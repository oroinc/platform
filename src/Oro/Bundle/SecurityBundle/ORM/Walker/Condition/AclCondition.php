<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Condition;

use Doctrine\ORM\Query\AST\PathExpression;

class AclCondition implements AclConditionInterface
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
     * @var string
     */
    protected $organizationField;

    /**
     * @var int|int[]
     */
    protected $organizationValue;

    /**
     * @var bool
     */
    protected $ignoreOwner;

    /**
     * @param string    $entityAlias
     * @param string    $entityField
     * @param mixed     $value
     * @param int       $pathExpressionType
     * @param string    $organizationField
     * @param int|int[] $organizationValue
     * @param bool      $ignoreOwner
     */
    public function __construct(
        $entityAlias,
        $entityField = null,
        $value = null,
        $pathExpressionType = PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION,
        $organizationField = null,
        $organizationValue = null,
        $ignoreOwner = false
    ) {
        $this->entityAlias        = $entityAlias;
        $this->entityField        = $entityField;
        $this->value              = $value;
        $this->pathExpressionType = $pathExpressionType;
        $this->organizationField  = $organizationField;
        $this->organizationValue  = $organizationValue;
        $this->ignoreOwner        = $ignoreOwner;
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

    /**
     * @return string
     */
    public function getOrganizationField()
    {
        return $this->organizationField;
    }

    /**
     * @return int|int[]
     */
    public function getOrganizationValue()
    {
        return $this->organizationValue;
    }

    /**
     * @param boolean $ignoreOwner
     */
    public function setIgnoreOwner($ignoreOwner)
    {
        $this->ignoreOwner = $ignoreOwner;
    }

    /**
     * @return boolean
     */
    public function isIgnoreOwner()
    {
        return $this->ignoreOwner;
    }
}
