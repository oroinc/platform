<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Represents API resource.
 */
class ApiResource
{
    private string $entityClass;
    /** @var string[] */
    private array $excludedActions = [];

    public function __construct(string $entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * Gets the class name of the entity.
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * Gets a list of actions that must not be available for the entity.
     *
     * @return string[]
     */
    public function getExcludedActions(): array
    {
        return $this->excludedActions;
    }

    /**
     * Sets a list of actions that must not be available for the entity.
     *
     * @param string[] $excludedActions
     */
    public function setExcludedActions(array $excludedActions): void
    {
        $this->excludedActions = $excludedActions;
    }

    /**
     * Indicates whether an action must not be available for the entity.
     */
    public function isExcludedAction(string $action): bool
    {
        return \in_array($action, $this->excludedActions, true);
    }

    /**
     * Adds an action to a list of actions that must not be available for the entity.
     */
    public function addExcludedAction(string $action): void
    {
        if (!\in_array($action, $this->excludedActions, true)) {
            $this->excludedActions[] = $action;
        }
    }

    /**
     * Removes an action from a list of actions that must not be available for the entity.
     */
    public function removeExcludedAction(string $action): void
    {
        $key = array_search($action, $this->excludedActions, true);
        if (false !== $key) {
            unset($this->excludedActions[$key]);
            $this->excludedActions = array_values($this->excludedActions);
        }
    }
}
