<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Represents the configuration of Data API resource actions.
 */
class ActionsConfig
{
    /** @var ActionConfig[] [action name => ActionConfig, ...] */
    protected $actions = [];

    /**
     * Gets a native PHP array representation of the configuration.
     *
     * @return array
     */
    public function toArray()
    {
        return ConfigUtil::convertObjectsToArray($this->actions);
    }

    /**
     * Indicates whether there is a configuration at least one action.
     *
     * @return bool
     */
    public function isEmpty()
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
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Gets the configuration of the action.
     *
     * @param string $actionName
     *
     * @return ActionConfig|null
     */
    public function getAction($actionName)
    {
        if (!isset($this->actions[$actionName])) {
            return null;
        }

        return $this->actions[$actionName];
    }

    /**
     * Adds the configuration of the action.
     *
     * @param string            $actionName
     * @param ActionConfig|null $action
     *
     * @return ActionConfig
     */
    public function addAction($actionName, ActionConfig $action = null)
    {
        if (null === $action) {
            $action = new ActionConfig();
        }

        $this->actions[$actionName] = $action;

        return $action;
    }

    /**
     * Removes the configuration of the action.
     *
     * @param string $actionName
     */
    public function removeAction($actionName)
    {
        unset($this->actions[$actionName]);
    }
}
