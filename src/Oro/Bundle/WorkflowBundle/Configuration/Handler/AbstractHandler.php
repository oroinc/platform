<?php

namespace Oro\Bundle\WorkflowBundle\Configuration\Handler;

abstract class AbstractHandler implements ConfigurationHandlerInterface
{
    /**
     * @param array $configuration
     * @param array $keys
     * @return array
     */
    protected function filterKeys(array $configuration, array $keys)
    {
        foreach ($configuration as $key => $value) {
            if (!in_array($key, $keys)) {
                unset($configuration[$key]);
            }
        }

        return $configuration;
    }

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
