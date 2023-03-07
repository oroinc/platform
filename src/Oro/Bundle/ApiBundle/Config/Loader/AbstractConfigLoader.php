<?php

namespace Oro\Bundle\ApiBundle\Config\Loader;

/**
 * The base class for configuration section loaders.
 */
abstract class AbstractConfigLoader implements ConfigLoaderInterface
{
    /**
     * @param object          $config
     * @param string|string[] $method
     * @param mixed           $value
     */
    protected function callSetter(object $config, string|array $method, mixed $value): void
    {
        if (\is_array($method)) {
            $config->{$method[$value ? 0 : 1]}();
        } else {
            $config->{$method}($value);
        }
    }

    protected function setValue(object $config, string $key, mixed $value): void
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
    protected function getSetter(object $config, string $key, array $methodMap): string|array|null
    {
        if (isset($methodMap[$key])) {
            return $methodMap[$key];
        }

        $setter = 'set' . $this->camelize($key);

        return method_exists($config, $setter)
            ? $setter
            : null;
    }

    protected function loadConfigValue(object $config, string $key, mixed $value, array $methodMap = []): void
    {
        $setter = $this->getSetter($config, $key, $methodMap);
        if (null !== $setter) {
            $this->callSetter($config, $setter, $value);
        } else {
            $this->setValue($config, $key, $value);
        }
    }

    protected function camelize(string $string): string
    {
        return strtr(ucwords(strtr($string, ['_' => ' '])), [' ' => '']);
    }
}
