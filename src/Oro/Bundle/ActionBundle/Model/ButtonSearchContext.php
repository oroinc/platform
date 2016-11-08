<?php

namespace Oro\Bundle\ActionBundle\Model;

class ButtonSearchContext
{
    /** @var  string */
    protected $entityClass;

    /** @var  mixed */
    protected $entityId;

    /** @var  string */
    protected $routeName;

    /** @var  string */
    protected $gridName;

    /** @var  string */
    protected $referrer;

    /** @var  string */
    protected $group;

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
     */
    public function setEntity($entityClass, $entityId = null)
    {
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param mixed $entityId
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
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
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
    }

    /**
     * @return string
     */
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * @param string $gridName
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;
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
     */
    public function setReferrer($referrer)
    {
        $this->referrer = $referrer;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }
}
