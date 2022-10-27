<?php

namespace Oro\Bundle\ActionBundle\Button;

/**
 * Represents action button context.
 */
class ButtonContext
{
    /** @var string */
    protected $entityClass;

    /** @var int|string|array|null */
    protected $entityId;

    protected string $routeName = '';

    /** @var string */
    protected $datagridName;

    /** @var string */
    protected $group;

    protected string $executionRoute = '';

    protected string $formDialogRoute = '';

    protected string $formPageRoute = '';

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

    public function getExecutionRoute(): string
    {
        return $this->executionRoute;
    }

    public function setExecutionRoute(string $executionRoute): self
    {
        $this->executionRoute = $executionRoute;

        return $this;
    }

    public function getFormDialogRoute(): string
    {
        return $this->formDialogRoute;
    }

    public function setFormDialogRoute(string $formDialogRoute): self
    {
        $this->formDialogRoute = $formDialogRoute;

        return $this;
    }

    public function getFormPageRoute(): string
    {
        return $this->formPageRoute;
    }

    public function setFormPageRoute(string $formPageRoute): self
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
