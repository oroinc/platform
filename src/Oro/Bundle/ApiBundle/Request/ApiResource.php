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

    /**
     * Indicates whether an action must not be available for the entity.
     *
     * @param string $action
     *
     * @return bool
     */
    public function isExcludedAction($action)
    {
        return in_array($action, $this->excludedActions, true);
    }

    /**
     * Adds an action to a list of actions that must not be available for the entity.
     *
     * @param string $action
     */
    public function addExcludedAction($action)
    {
        if (!in_array($action, $this->excludedActions, true)) {
            $this->excludedActions[] = $action;
        }
    }

    /**
     * Removes an action from a list of actions that must not be available for the entity.
     *
     * @param string $action
     */
    public function removeExcludedAction($action)
    {
        $key = array_search($action, $this->excludedActions, true);
        if (false !== $key) {
            unset($this->excludedActions[$key]);
            $this->excludedActions = array_values($this->excludedActions);
        }
    }
}
