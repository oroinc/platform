<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of API resource actions.
 */
class ActionsConfig
{
    /** @var ActionConfig[] [action name => ActionConfig, ...] */
    private array $actions = [];

    /**
     * Gets a native PHP array representation of the configuration.
     */
    public function toArray(): array
    {
        return ConfigUtil::convertObjectsToArray($this->actions);
    }

    /**
     * Indicates whether there is a configuration at least one action.
     */
    public function isEmpty(): bool
    {
        return empty($this->actions);
    }

    /**
     * Makes a deep copy of the object.
     */
    public function __clone()
    {
        $this->actions = ConfigUtil::cloneObjects($this->actions);
    }

    /**
     * Gets the configuration for all actions.
     *
     * @return ActionConfig[] [action name => ActionConfig, ...]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Gets the configuration of the action.
     */
    public function getAction(string $actionName): ?ActionConfig
    {
        return $this->actions[$actionName] ?? null;
    }

    /**
     * Adds the configuration of the action.
     */
    public function addAction(string $actionName, ActionConfig $action = null): ActionConfig
    {
        if (null === $action) {
            $action = new ActionConfig();
        }

        $this->actions[$actionName] = $action;

        return $action;
    }

    /**
     * Removes the configuration of the action.
     */
    public function removeAction(string $actionName): void
    {
        unset($this->actions[$actionName]);
    }
}
