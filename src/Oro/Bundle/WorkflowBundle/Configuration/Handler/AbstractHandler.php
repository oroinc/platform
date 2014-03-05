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
}
