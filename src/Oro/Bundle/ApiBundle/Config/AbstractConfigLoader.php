<?php

namespace Oro\Bundle\ApiBundle\Config;

/**
 * A base class for configuration section loaders.
 */
abstract class AbstractConfigLoader implements ConfigLoaderInterface
{
    /**
     * @param object          $config
     * @param string|string[] $method
     * @param mixed           $value
     */
    protected function callSetter($config, $method, $value)
    {
        if (\is_array($method)) {
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

    /**
     * @param object $config
     * @param string $key
     * @param array  $methodMap
     *
     * @return string|string[]|null
     */
    protected function getSetter($config, $key, array $methodMap)
    {
        if (isset($methodMap[$key])) {
            return $methodMap[$key];
        }

        $setter = 'set' . $this->camelize($key);

        return \method_exists($config, $setter)
            ? $setter
            : null;
    }

    /**
     * @param object $config
     * @param string $key
     * @param mixed  $value
     * @param array  $methodMap
     */
    protected function loadConfigValue($config, $key, $value, array $methodMap = [])
    {
        $setter = $this->getSetter($config, $key, $methodMap);
        if (null !== $setter) {
            $this->callSetter($config, $setter, $value);
        } else {
            $this->setValue($config, $key, $value);
        }
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function camelize($string)
    {
        return strtr(\ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
    }
}
