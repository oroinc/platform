<?php

namespace Oro\Bundle\ActionBundle\Model;

/**
 * Storage for Action Group Definition.
 */
class ActionGroupDefinition
{
    /** @var string */
    private $name;

    /** @var array */
    private $actions = [];

    /** @var array */
    private $conditions = [];

    /** @var array */
    private $parameters = [];

    /** @var string|array|null */
    private $aclResource = null;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array $actions
     * @return $this
     */
    public function setActions(array $actions)
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @return array
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getAclResource(): string|array|null
    {
        return $this->aclResource;
    }

    public function setAclResource(string|array|null $aclResource): self
    {
        $this->aclResource = $aclResource;

        return $this;
    }
}
