<?php

namespace Oro\Component\Config\Merger;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\UIBundle\Tools\ArrayUtils;

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
        $this->kernelBundles = array_values($kernelBundles);
    }

    /**
     * @param array $rawConfigurationByBundles
     * @return array
     */
    public function mergeConfiguration(array $rawConfigurationByBundles)
    {
        $configs = $this->prepareRawConfiguration($rawConfigurationByBundles);

        foreach ($configs as $actionName => $actionConfigs) {
            $data = array_shift($actionConfigs);

            foreach ($actionConfigs as $config) {
                $data = $this->merge($data, $config);
            }

            $configs[$actionName] = (array)$data;
        }

        foreach ($configs as $actionName => &$actionConfig) {
            $this->resolveExtends($configs, $actionConfig, $actionName);
        }

        return $configs;
    }

    /**
     * @param array $rawConfigurationByBundles
     * @return array
     */
    protected function prepareRawConfiguration(array $rawConfigurationByBundles)
    {
        $actionConfigs = [];

        foreach ($rawConfigurationByBundles as $bundle => $actions) {
            $bundleNumber = array_search($bundle, $this->kernelBundles, true);

            if ($bundleNumber === false) {
                continue;
            }

            foreach ($actions as $actionName => $config) {
                $actionConfigs[$actionName][$bundleNumber] = $config;
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
        unset($data[self::REPLACES_NODE_NAME], $config[self::REPLACES_NODE_NAME]);

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
                if (!array_key_exists($key, $data)) {
                    $data[$key] = $value;
                } else {
                    if (is_array($value)) {
                        $data[$key] = $this->merge($data[$key], $value);
                    } else {
                        $data[$key] = $value;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @param array $configs
     * @param array $config
     * @param string $actionName
     * @throws InvalidConfigurationException
     */
    protected function resolveExtends(array &$configs, array &$config, $actionName)
    {
        $this->processedConfigs[] = $actionName;

        if (!array_key_exists(self::EXTENDS_NODE_NAME, $config) || empty($config[self::EXTENDS_NODE_NAME])) {
            return;
        }

        $extends = $config[self::EXTENDS_NODE_NAME];
        if (!array_key_exists($extends, $configs)) {
            throw new InvalidConfigurationException(
                sprintf('Could not found configuration of %s for dependant configuration %s.', $extends, $actionName)
            );
        }

        $extendsConfig = &$configs[$extends];
        if (array_key_exists(self::EXTENDS_NODE_NAME, $extendsConfig)) {
            if (in_array($extends, $this->processedConfigs, true)) {
                throw new InvalidConfigurationException(
                    sprintf('Found circular "extends" references %s and %s configurations.', $extends, $actionName)
                );
            }

            $this->resolveExtends($configs, $extendsConfig, $extends);
        }

        $config = ArrayUtils::arrayMergeRecursiveDistinct($extendsConfig, $config);
        unset($config[self::EXTENDS_NODE_NAME]);
    }
}
