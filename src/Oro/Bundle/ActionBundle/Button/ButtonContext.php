<?php

namespace Oro\Bundle\ActionBundle\Button;

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
    protected $executionRoute;

    /** @var string */
    protected $formDialogRoute;

    /** @var string */
    protected $formPageRoute;

    /** @var string */
    protected $originalUrl;

    /** @var bool */
    protected $enabled = true;

    /** @var array */
    protected $errors = [];

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
     * @return int|string|array|null
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
    public function getExecutionRoute()
    {
        return $this->executionRoute;
    }

    /**
     * @param string $executionRoute
     *
     * @return $this
     */
    public function setExecutionRoute($executionRoute)
    {
        $this->executionRoute = $executionRoute;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormDialogRoute()
    {
        return $this->formDialogRoute;
    }

    /**
     * @param string $formDialogRoute
     *
     * @return $this
     */
    public function setFormDialogRoute($formDialogRoute)
    {
        $this->formDialogRoute = $formDialogRoute;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormPageRoute()
    {
        return $this->formPageRoute;
    }

    /**
     * @param string $formPageRoute
     *
     * @return $this
     */
    public function setFormPageRoute($formPageRoute)
    {
        $this->formPageRoute = $formPageRoute;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalUrl()
    {
        return $this->originalUrl;
    }

    /**
     * @param string $originalUrl
     *
     * @return $this
     */
    public function setOriginalUrl($originalUrl)
    {
        $this->originalUrl = $originalUrl;

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

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param array $errors
     *
     * @return $this
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;

        return $this;
    }
}
