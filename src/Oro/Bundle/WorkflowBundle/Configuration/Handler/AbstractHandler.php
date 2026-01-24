<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

/**
 * Provides common functionality for workflow configuration handlers.
 *
 * This base class implements helper methods for checking entity presence in configuration groups.
 * Subclasses should extend this to implement specific configuration handling logic
 * for different workflow configuration aspects.
 */
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
