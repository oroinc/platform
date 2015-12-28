<?php

namespace Oro\Component\Config\Merger;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ConfigurationMerger
{
    const EXTENDS_NODE_NAME = 'extends';
    const REPLACES_NODE_NAME = 'replace';

    /** @var array */
    protected $kernelBundles;

    /** @var array */
    protected $processedConfigs = [];

    /**
     * @param array $kernelBundles Must be passed value of container`s parameter %kernel.bundles%
     */
    public function __construct(array $kernelBundles)
    {
        $this->kernelBundles = array_flip(array_values($kernelBundles));
    }

    /**
     * @param array $rawConfigurationByBundles
     * @return array
     */
    public function mergeConfiguration(array $rawConfigurationByBundles)
    {
        $actions = $this->prepareRawConfiguration($rawConfigurationByBundles);

        foreach ($actions as &$configs) {
            $data = array_shift($configs);

            foreach ($configs as $config) {
                $data = $this->merge($data, $config);
            }

            $configs = (array)$data;
        }
        unset($configs);

        foreach ($actions as $actionName => &$actionConfig) {
            $actionConfig = $this->unsetReplaceNodeRecursive(
                $this->resolveExtends($actions, $actionName)
            );
        }

        return $actions;
    }

    /**
     * @param array $rawConfigurationByBundles
     * @return array
     */
    protected function prepareRawConfiguration(array $rawConfigurationByBundles)
    {
        $actionConfigs = [];

        foreach ($rawConfigurationByBundles as $bundle => $actions) {
            if (array_key_exists($bundle, $this->kernelBundles)) {
                $bundleNumber = $this->kernelBundles[$bundle];

                foreach ($actions as $actionName => $config) {
                    $actionConfigs[$actionName][$bundleNumber] = $config;
                }
            }
        }

        return array_map(
            function ($configs) {
                ksort($configs);
                return $configs;
            },
            $actionConfigs
        );
    }

    /**
     * @param array $data
     * @param array $config
     * @return array
     */
    protected function merge(array $data, array $config)
    {
        $replaces = empty($config[self::REPLACES_NODE_NAME]) ? [] : (array)$config[self::REPLACES_NODE_NAME];

        foreach ($replaces as $key) {
            if (empty($config[$key])) {
                unset($data[$key]);
            } else {
                $data[$key] = $config[$key];
                unset($config[$key]);
            }
        }

        foreach ($config as $key => $value) {
            if (is_int($key)) {
                $data[] = $value;
            } else {
                if (array_key_exists($key, $data) && is_array($value)) {
                    $data[$key] = $this->merge($data[$key], $value);
                } else {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    /**
     * @param array $configs
     * @param string $actionName
     * @return array
     * @throws InvalidConfigurationException
     */
    protected function resolveExtends(array $configs, $actionName)
    {
        if (!in_array($actionName, $this->processedConfigs, true)) {
            $this->processedConfigs[] = $actionName;
        }

        $config = $configs[$actionName];
        if (empty($config[self::EXTENDS_NODE_NAME])) {
            return $config;
        }

        $extends = $config[self::EXTENDS_NODE_NAME];
        unset($config[self::EXTENDS_NODE_NAME]);

        if (!array_key_exists($extends, $configs)) {
            throw new InvalidConfigurationException(
                sprintf('Could not found configuration of %s for dependant configuration %s.', $extends, $actionName)
            );
        }

        if (array_key_exists(self::EXTENDS_NODE_NAME, $configs[$extends])) {
            if (in_array($extends, $this->processedConfigs, true)) {
                throw new InvalidConfigurationException(
                    sprintf('Found circular "extends" references %s and %s configurations.', $extends, $actionName)
                );
            }

            $configs[$extends] = $this->resolveExtends($configs, $extends);
        }

        return $this->merge($configs[$extends], $config);
    }

    /**
     * @param array $config
     * @return array
     */
    protected function unsetReplaceNodeRecursive(array $config)
    {
        if (array_key_exists(self::REPLACES_NODE_NAME, $config)) {
            unset($config[self::REPLACES_NODE_NAME]);
        }

        foreach ($config as &$subConfig) {
            if (is_array($subConfig)) {
                $subConfig = $this->unsetReplaceNodeRecursive($subConfig);
            }
        }

        return $config;
    }
}
