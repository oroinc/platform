<?php

namespace Oro\Bundle\ActionBundle\Model;

class ButtonSearchContext
{
    /** @var string */
    protected $entityClass;

    /** @var mixed */
    protected $entityId;

    /** @var string */
    protected $routeName;

    /** @var string */
    protected $gridName;

    /** @var string */
    protected $referrer;

    /** @var string */
    protected $group;

    /**
     * @param string $entityClass
     * @param null|mixed $entityId
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
     * @return mixed
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
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * @param string $gridName
     * @return $this
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;

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
     * @param string $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }
}
