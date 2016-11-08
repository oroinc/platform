<?php

namespace Oro\Bundle\ActionBundle\Model;

class ButtonContext
{
    /** @var  string */
    protected $entityClass;

    /** @var  int */
    protected $entityId;

    /** @var  string */
    protected $routeName;

    /** @var  string */
    protected $datagridName;

    /** @var  string */
    protected $group;

    /** @var  string */
    protected $executionUrl;

    /** @var  string */
    protected $dialogUrl;

    /** @var  bool */
    protected $enabled;

    /** @var  bool */
    protected $unavailableHidden;

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param string $entityClass
     * @param int|null $entityId
     */
    public function setEntity($entityClass, $entityId = null)
    {
        $this->entityClass = $entityClass;
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
    public function getDatagridName()
    {
        return $this->datagridName;
    }

    /**
     * @param string $datagridName
     */
    public function setDatagridName($datagridName)
    {
        $this->datagridName = $datagridName;
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

    /**
     * @return string
     */
    public function getExecutionUrl()
    {
        return $this->executionUrl;
    }

    /**
     * @param string $executionUrl
     */
    public function setExecutionUrl($executionUrl)
    {
        $this->executionUrl = $executionUrl;
    }

    /**
     * @return string
     */
    public function getDialogUrl()
    {
        return $this->dialogUrl;
    }

    /**
     * @param string $dialogUrl
     */
    public function setDialogUrl($dialogUrl)
    {
        $this->dialogUrl = $dialogUrl;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isUnavailableHidden()
    {
        return $this->unavailableHidden;
    }

    /**
     * @param bool $unavailableHidden
     */
    public function setUnavailableHidden($unavailableHidden)
    {
        $this->unavailableHidden = $unavailableHidden;
    }
}
