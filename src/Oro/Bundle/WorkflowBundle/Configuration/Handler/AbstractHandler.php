<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

abstract class AbstractHandler implements ConfigurationHandlerInterface
{
    /**
     * @param array $configuration
     * @param string $groupKey
     * @param string $entityName
     * @return bool
     */
    protected function hasEntityInGroup(array $configuration, $groupKey, $entityName)
    {
        $entities = array();
        if (!empty($configuration[$groupKey])) {
            $entities = $configuration[$groupKey];
        }

        foreach ($entities as $key => $entity) {
            if (!empty($entity['name']) && $entity['name'] == $entityName
                || $key === $entityName
            ) {
                return true;
            }
        }

        return false;
    }
}
