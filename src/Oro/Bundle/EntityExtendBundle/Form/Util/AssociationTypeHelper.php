<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;

/**
 * Helper to work with associations
 */
class AssociationTypeHelper extends ConfigTypeHelper
{
    private array $owningSideEntities = [];

    /**
     * Checks if the given entity is included in 'dictionary' group
     *
     * @param string $className
     *
     * @return bool
     */
    public function isDictionary($className)
    {
        $groupingConfigProvider = $this->configManager->getProvider('grouping');
        if ($groupingConfigProvider->hasConfig($className)) {
            $groups = $groupingConfigProvider->getConfig($className)->get('groups');
            if (!empty($groups) && \in_array('dictionary', $groups, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns all entities included in the given group
     *
     * @param string $groupName
     *
     * @return string[] The list of class names
     */
    public function getOwningSideEntities($groupName)
    {
        if (!isset($this->owningSideEntities[$groupName])) {
            $this->owningSideEntities[$groupName] = $this->loadOwningSideEntities($groupName);
        }

        return $this->owningSideEntities[$groupName];
    }

    private function loadOwningSideEntities(string $groupName): array
    {
        $result  = [];
        $configs = $this->configManager->getProvider('grouping')->getConfigs();
        foreach ($configs as $config) {
            $groups = $config->get('groups');
            if ($groups && \in_array($groupName, $groups, true)) {
                $result[] = $config->getId()->getClassName();
            }
        }

        return $result;
    }
}
