<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Condition;

class AclNullCondition implements AclConditionInterface
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
     * @var boolean
     */
    protected $not;

    /**
     * @param string  $entityAlias
     * @param string  $entityField
     * @param boolean $not
     */
    public function __construct(
        $entityAlias,
        $entityField,
        $not = false
    ) {
        $this->entityAlias = $entityAlias;
        $this->entityField = $entityField;
        $this->not = $not;
    }

    /**
     * @return string
     */
    public function getEntityAlias()
    {
        return $this->entityAlias;
    }

    /**
     * @param string $entityAlias
     *
     * @return AclNullCondition
     */
    public function setEntityAlias($entityAlias)
    {
        $this->entityAlias = $entityAlias;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityField()
    {
        return $this->entityField;
    }

    /**
     * @param string $entityField
     *
     * @return AclNullCondition
     */
    public function setEntityField($entityField)
    {
        $this->entityField = $entityField;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isNot()
    {
        return $this->not;
    }

    /**
     * @param boolean $not
     *
     * @return AclNullCondition
     */
    public function setNot($not = null)
    {
        $this->not = $not;

        return $this;
    }
}
