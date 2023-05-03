<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Represents API sub-resource.
 */
class ApiSubresource
{
    private ?string $targetClassName = null;
    /** @var string[]|null */
    private ?array $acceptableTargetClassNames = null;
    private bool $isCollection = false;
    /** @var string[] */
    private array $excludedActions = [];

    /**
     * Gets the target entity class name.
     */
    public function getTargetClassName(): string
    {
        return $this->targetClassName ?? '';
    }

    /**
     * Sets the target entity class name.
     */
    public function setTargetClassName(string $className): void
    {
        $this->targetClassName = $className;
    }

    /**
     * Gets acceptable target entity class names.
     *
     * @return string[]
     */
    public function getAcceptableTargetClassNames(): array
    {
        return $this->acceptableTargetClassNames ?? [];
    }

    /**
     * Sets acceptable target entity class names.
     *
     * @param string[] $classNames
     */
    public function setAcceptableTargetClassNames(array $classNames): void
    {
        $this->acceptableTargetClassNames = $classNames;
    }

    /**
     * Whether the sub-resource represents "to-many" or "to-one" association.
     */
    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    /**
     * Sets a flag indicates whether the sub-resource represents "to-many" or "to-one" association.
     *
     * @param bool $isCollection TRUE for "to-many" relationship, FALSE for "to-one" relationship
     */
    public function setIsCollection(bool $isCollection): void
    {
        $this->isCollection = $isCollection;
    }

    /**
     * Gets a list of actions that must not be available for the sub-resource.
     *
     * @return string[]
     */
    public function getExcludedActions(): array
    {
        return $this->excludedActions;
    }

    /**
     * Sets a list of actions that must not be available for the sub-resource.
     *
     * @param string[] $excludedActions
     */
    public function setExcludedActions(array $excludedActions): void
    {
        $this->excludedActions = $excludedActions;
    }

    /**
     * Indicates whether an action must not be available for the sub-resource.
     */
    public function isExcludedAction(string $action): bool
    {
        return \in_array($action, $this->excludedActions, true);
    }

    /**
     * Adds an action to a list of actions that must not be available for the sub-resource.
     */
    public function addExcludedAction(string $action): void
    {
        if (!\in_array($action, $this->excludedActions, true)) {
            $this->excludedActions[] = $action;
        }
    }

    /**
     * Removes an action from a list of actions that must not be available for the sub-resource.
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
