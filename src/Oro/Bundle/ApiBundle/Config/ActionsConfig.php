<?php

namespace Oro\Bundle\ApiBundle\Config;

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
        $result = [];
        if (!empty($this->actions)) {
            foreach ($this->actions as $actionName => $action) {
                $actionConfig = $action->toArray();
                if (!empty($actionConfig)) {
                    $result[$actionName] = $actionConfig;
                }
            }
        }

        return $result;
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
     * Make a deep copy of object.
     */
    public function __clone()
    {
        array_walk(
            $this->actions,
            function (&$action) {
                $action = clone $action;
            }
        );
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
        return isset($this->actions[$actionName])
            ? $this->actions[$actionName]
            : null;
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
