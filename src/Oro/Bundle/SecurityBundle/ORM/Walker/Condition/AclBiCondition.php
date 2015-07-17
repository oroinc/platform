<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker\Condition;

class AclBiCondition implements AclConditionInterface
{
    /**
     * @var string
     */
    protected $entityAliasLeft;

    /**
     * @var string
     */
    protected $entityFieldLeft;

    /**
     * @var string
     */
    protected $entityAliasRight;

    /**
     * @var string
     */
    protected $entityFieldRight;

    /**
     * @param string $entityAliasLeft
     * @param string $entityFieldLeft
     * @param string $entityAliasRight
     * @param string $entityFieldRight
     */
    public function __construct(
        $entityAliasLeft,
        $entityFieldLeft,
        $entityAliasRight,
        $entityFieldRight
    ) {
        $this->entityAliasLeft = $entityAliasLeft;
        $this->entityFieldLeft = $entityFieldLeft;
        $this->entityAliasRight = $entityAliasRight;
        $this->entityFieldRight = $entityFieldRight;
    }

    /**
     * @return string
     */
    public function getEntityAliasLeft()
    {
        return $this->entityAliasLeft;
    }

    /**
     * @param string $entityAliasLeft
     *
     * @return AclBiCondition
     */
    public function setEntityAliasLeft($entityAliasLeft)
    {
        $this->entityAliasLeft = $entityAliasLeft;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityFieldLeft()
    {
        return $this->entityFieldLeft;
    }

    /**
     * @param string $entityFieldLeft
     *
     * @return AclBiCondition
     */
    public function setEntityFieldLeft($entityFieldLeft)
    {
        $this->entityFieldLeft = $entityFieldLeft;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityAliasRight()
    {
        return $this->entityAliasRight;
    }

    /**
     * @param string $entityAliasRight
     *
     * @return AclBiCondition
     */
    public function setEntityAliasRight($entityAliasRight)
    {
        $this->entityAliasRight = $entityAliasRight;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityFieldRight()
    {
        return $this->entityFieldRight;
    }

    /**
     * @param string $entityFieldRight
     *
     * @return AclBiCondition
     */
    public function setEntityFieldRight($entityFieldRight)
    {
        $this->entityFieldRight = $entityFieldRight;

        return $this;
    }
}
