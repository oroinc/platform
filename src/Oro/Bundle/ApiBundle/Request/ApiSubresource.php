<?php

namespace Oro\Bundle\ApiBundle\Request;

class ApiSubresource
{
    /** @var string */
    protected $targetClassName;

    /** @var string[] */
    protected $acceptableTargetClassNames;

    /** @var bool */
    protected $isCollection;

    /** @var string[] */
    protected $excludedActions = [];

    /**
     * Gets the target entity class name.
     *
     * @return string
     */
    public function getTargetClassName()
    {
        return $this->targetClassName;
    }

    /**
     * Sets the target entity class name.
     *
     * @param string $className
     */
    public function setTargetClassName($className)
    {
        $this->targetClassName = $className;
    }

    /**
     * Gets acceptable target entity class names.
     *
     * @return string[]
     */
    public function getAcceptableTargetClassNames()
    {
        return null !== $this->acceptableTargetClassNames
            ? $this->acceptableTargetClassNames
            : [];
    }

    /**
     * Sets acceptable target entity class names.
     *
     * @param string[] $classNames
     */
    public function setAcceptableTargetClassNames(array $classNames)
    {
        $this->acceptableTargetClassNames = $classNames;
    }

    /**
     * Whether the sub-resource represents "to-many" or "to-one" association.
     *
     * @return bool
     */
    public function isCollection()
    {
        return (bool)$this->isCollection;
    }

    /**
     * Sets a flag indicates whether the sub-resource represents "to-many" or "to-one" association.
     *
     * @param bool $isCollection TRUE for "to-many" relation, FALSE for "to-one" relation
     */
    public function setIsCollection($isCollection)
    {
        $this->isCollection = $isCollection;
    }

    /**
     * Gets a list of actions that must not be available for the sub-resource.
     *
     * @return string[]
     */
    public function getExcludedActions()
    {
        return $this->excludedActions;
    }

    /**
     * Sets a list of actions that must not be available for the sub-resource.
     *
     * @param string[] $excludedActions
     */
    public function setExcludedActions(array $excludedActions)
    {
        $this->excludedActions = $excludedActions;
    }

    /**
     * Indicates whether an action must not be available for the sub-resource.
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
     * Adds an action to a list of actions that must not be available for the sub-resource.
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
     * Removes an action from a list of actions that must not be available for the sub-resource.
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
