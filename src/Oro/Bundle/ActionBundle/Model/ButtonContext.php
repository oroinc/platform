<?php

namespace Oro\Bundle\ActionBundle\Model;

class ButtonContext
{
    /** @var string */
    protected $entityClass;

    /** @var int|string|array|null */
    protected $entityId;

    /** @var string */
    protected $routeName;

    /** @var string */
    protected $datagridName;

    /** @var string */
    protected $group;

    /** @var string */
    protected $executionUrl;

    /** @var string */
    protected $dialogUrl;

    /** @var bool */
    protected $enabled;

    /** @var bool */
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
     * @param int|string|array|null $entityId
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
    public function getDatagridName()
    {
        return $this->datagridName;
    }

    /**
     * @param string $datagridName
     *
     * @return $this
     */
    public function setDatagridName($datagridName)
    {
        $this->datagridName = $datagridName;

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
    public function getExecutionUrl()
    {
        return $this->executionUrl;
    }

    /**
     * @param string $executionUrl
     *
     * @return $this
     */
    public function setExecutionUrl($executionUrl)
    {
        $this->executionUrl = $executionUrl;

        return $this;
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
     *
     * @return $this
     */
    public function setDialogUrl($dialogUrl)
    {
        $this->dialogUrl = $dialogUrl;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
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
     *
     * @return $this
     */
    public function setUnavailableHidden($unavailableHidden)
    {
        $this->unavailableHidden = $unavailableHidden;

        return $this;
    }
}
