<?php

namespace Oro\Bundle\ActionBundle\Button;

/**
 * Represents action button search context.
 */
class ButtonSearchContext
{
    /** @var string */
    protected $entityClass;

    /** @var int|string|array */
    protected $entityId;

    protected string $routeName = '';

    /** @var string */
    protected $datagrid;

    /** @var string */
    protected $referrer;

    /** @var string */
    protected $group;

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     *
     * @return $this
     */
    public function setEntity($entityClass, $entityId = null)
    {
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return int|string|array
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): self
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getDatagrid()
    {
        return $this->datagrid;
    }

    /**
     * @param string $datagrid
     *
     * @return $this
     */
    public function setDatagrid($datagrid)
    {
        $this->datagrid = $datagrid;

        return $this;
    }

    /**
     * @return string
     */
    public function getReferrer()
    {
        return $this->referrer;
    }

    /**
     * @param string $referrer
     *
     * @return $this
     */
    public function setReferrer($referrer)
    {
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string|array $group
     *
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return md5(
            serialize(
                [
                    $this->entityClass,
                    $this->entityId,
                    $this->routeName,
                    $this->datagrid,
                    $this->referrer,
                    $this->group
                ]
            )
        );
    }
}
