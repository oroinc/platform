<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Oro\Bundle\ActionBundle\Model\Operation;

class RestrictHelper
{
    /**
     * $groups param can be array of groups, null or string with group
     * if $group is null - return all operations without restrictions
     * if $groups is false - restrict only operations which not have group
     * if $groups is array - restrict only operations which in this array with groups
     * if $groups is string - restrict only operations which equals this string with group
     *
     * @param Operation[] $operations
     * @param array|bool|string|null $groups
     * @return Operation[]
     */
    public function restrictOperationsByGroup($operations, $groups = null)
    {
        if (null === $groups) {
            return $operations;
        }

        $groups = $groups === false ? false : (array)$groups;
        $restrictedOperations = [];
        foreach ($operations as $key => $operation) {
            $buttonOptions = $operation->getDefinition()->getButtonOptions();
            if (array_key_exists('group', $buttonOptions)) {
                if ($groups !== false && in_array($buttonOptions['group'], $groups, true)) {
                    $restrictedOperations[$key] = $operation;
                }
            } elseif ($groups === false) {
                $restrictedOperations[$key] = $operation;
            }
        }

        return $restrictedOperations;
    }
}
