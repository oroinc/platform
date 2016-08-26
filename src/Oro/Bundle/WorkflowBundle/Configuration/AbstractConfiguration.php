<?php

namespace Oro\Bundle\WorkflowBundle\Configuration;

abstract class AbstractConfiguration
{
    /**
     * @param array $fields
     * @param array $config
     * @return array
     */
    protected function mergeConfigs(array $fields, array $config = null)
    {
        if ($config) {
            foreach ($fields as $originalName => $aliasName) {
                if (isset($config[$aliasName])) {
                    $config[$originalName] = array_merge(
                        isset($config[$originalName]) ? $config[$originalName] : [],
                        $config[$aliasName]
                    );
                    unset($config[$aliasName]);
                }
            }
        }

        return $config;
    }
}
