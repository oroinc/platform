<?php

namespace Oro\Bundle\ApiBundle\Config;

abstract class AbstractConfigLoader implements ConfigLoaderInterface
{
    /**
     * @param object          $config
     * @param string|string[] $method
     * @param mixed           $value
     */
    protected function callSetter($config, $method, $value)
    {
        if (is_array($method)) {
            $config->{$method[$value ? 0 : 1]}();
        } else {
            $config->{$method}($value);
        }
    }

    /**
     * @param object $config
     * @param string $key
     * @param mixed  $value
     */
    protected function setValue($config, $key, $value)
    {
        $config->set($key, $value);
    }
}
