<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Model\Action;

class RestrictHelper
{
    /**
     * $groups param can be array of groups, null or string with group
     * if $group is null - return all actions without restrictions
     * if $groups is false - restrict only actions which not have group
     * if $groups is array - restrict only actions which in this array with groups
     * if $groups is string - restrict only actions which equals this string with group
     *
     * @param Action[] $actions
     * @param array|bool|string|null $groups
     * @return Action[]
     */
    public function restrictActionsByGroup($actions, $groups = null)
    {
        if (null === $groups) {
            return $actions;
        }

        $groups = $groups === false ? false : (array)$groups;
        $restrictedActions = [];
        foreach ($actions as $key => $action) {
            $buttonOptions = $action->getDefinition()->getButtonOptions();
            if (array_key_exists('group', $buttonOptions)) {
                if ($groups !== false && in_array($buttonOptions['group'], $groups, true)) {
                    $restrictedActions[$key] = $action;
                }
            } elseif ($groups === false) {
                $restrictedActions[$key] = $action;
            }
        }

        return $restrictedActions;
    }
}
