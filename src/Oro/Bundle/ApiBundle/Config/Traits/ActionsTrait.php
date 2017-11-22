<?php

namespace Oro\Bundle\ApiBundle\Config\Traits;

use Oro\Bundle\ApiBundle\Config\ActionConfig;

/**
 * @property ActionConfig[] $actions [action name => ActionConfig, ...]
 */
trait ActionsTrait
{
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
