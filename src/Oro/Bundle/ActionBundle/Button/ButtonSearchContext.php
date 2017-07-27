<?php

namespace Oro\Bundle\ActionBundle\Button;

class ButtonSearchContext
{
    /** @var string */
    protected $entityClass;

    /** @var int|string|array */
    protected $entityId;

    /** @var string */
    protected $routeName;

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

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     *
     * @return $this
     */
    public function setRouteName($routeName)
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
