<?php

namespace Oro\Bundle\ApiBundle\Request;

class ApiResource
{
    /** @var string */
    protected $entityClass;

    /** @var string[] */
    protected $excludedActions = [];

    /**
     * @param $entityClass
     */
    public function __construct($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Gets the class name of the entity.
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Gets a list of actions that must not be available for the entity.
     *
     * @return string[]
     */
    public function getExcludedActions()
    {
        return $this->excludedActions;
    }

    /**
     * Sets a list of actions that must not be available for the entity.
     *
     * @param string[] $excludedActions
     */
    public function setExcludedActions(array $excludedActions)
    {
        $this->excludedActions = $excludedActions;
    }
}
