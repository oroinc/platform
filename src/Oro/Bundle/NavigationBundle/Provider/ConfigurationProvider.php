<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Doctrine\Common\Cache\Cache;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\NavigationBundle\DependencyInjection\Configuration;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;

class ConfigurationProvider
{
    const COMPILER_PASS_NAME = 'oro_navigation';
    const CACHE_KEY = 'oro_navigation.configuration_data';
    const NAVIGATION_CONFIG_ROOT = 'navigation';

    const NAVIGATION_ELEMENTS_KEY = 'navigation_elements';
    const MENU_CONFIG_KEY = 'menu_config';
    const TITLES_KEY = 'titles';

    /** @var Cache */
    private $cache;

    /** @var array */
    private $rawConfiguration = [];

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Returns config for current group
     *
     * @param string $groupKey configuration group key
     *
     * @return array
     */
    public function getConfiguration($groupKey)
    {
        $rawConfiguration = $this->getRawConfiguration();

        if (array_key_exists($groupKey, $rawConfiguration)) {
            return $rawConfiguration[$groupKey];
        }

        return [];
    }

    /**
     * Loads configurations and save them in cache
     *
     * @param ContainerBuilder $container The container builder
     *                                    If NULL the loaded resources will not be registered in the container
     *                                    and as result will not be monitored for changes
     *
     * @return ConfigurationProvider
     */
    public function loadConfiguration(ContainerBuilder $container = null)
    {
        $config = [];

        $configLoader = $this->getConfigurationLoader();
        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (array_key_exists(self::NAVIGATION_CONFIG_ROOT, $resource->data)) {
                $resourceConfig = $resource->data[self::NAVIGATION_CONFIG_ROOT];
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $resourceConfig);
            }
        }

        $rawConfiguration = &$config[self::MENU_CONFIG_KEY];
        if (is_array($rawConfiguration) && array_key_exists('tree', $rawConfiguration)) {
            foreach ($rawConfiguration['tree'] as $type => &$menuPartConfig) {
                if (isset($rawConfiguration['tree'][$type])
                    && is_array($rawConfiguration['tree'][$type])
                    && is_array($menuPartConfig)
                ) {
                    $this->reorganizeTree($rawConfiguration['tree'][$type], $menuPartConfig);
                }
            }
            unset($menuPartConfig);
        }

        // validate configuration
        $this->rawConfiguration = $this->processConfiguration($config);
        unset($this->rawConfiguration['settings']);

        $this->normalizeOptionNames($this->rawConfiguration[self::MENU_CONFIG_KEY]);

        $this->cache->save(self::CACHE_KEY, $this->rawConfiguration);

        return $this;
    }

    /**
     * @return array
     */
    private function getRawConfiguration()
    {
        $this->ensureConfigurationLoaded();

        return $this->rawConfiguration;
    }

    /**
     * Make sure that configuration is loaded
     */
    private function ensureConfigurationLoaded()
    {
        if (count($this->rawConfiguration) === 0) {
            if (!$this->cache->contains(self::CACHE_KEY)) {
                $this->loadConfiguration();
            } else {
                $this->rawConfiguration = $this->cache->fetch(self::CACHE_KEY);
            }
        }

        return $this;
    }

    /**
     * @param array $config
     * @param array $configPart
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function reorganizeTree(array &$config, array &$configPart)
    {
        if (!empty($configPart['children'])) {
            foreach ($configPart['children'] as $childName => &$childConfig) {
                if (isset($childConfig['merge_strategy']) && $childConfig['merge_strategy'] !== 'append') {
                    if ($childConfig['merge_strategy'] === 'move') {
                        $existingItem = $this->getMenuItemByName($config, $childName);
                        if (!empty($existingItem['children'])) {
                            $childChildren = isset($childConfig['children']) ? $childConfig['children'] : [];
                            $childConfig['children'] = array_merge($existingItem['children'], $childChildren);
                        }
                    }
                    $this->removeItem($config, $childName);
                } elseif (is_array($childConfig)) {
                    $this->reorganizeTree($config, $childConfig);
                }
            }
        }
    }

    /**
     * @param array $config
     * @param       $childName
     *
     * @return array|null
     */
    private function getMenuItemByName(array $config, $childName)
    {
        if (!empty($config['children'])) {
            foreach ($config['children'] as $key => $configRow) {
                if ($key === $childName) {
                    return $config['children'][$childName];
                } elseif (is_array($configRow)) {
                    return $this->getMenuItemByName($configRow, $childName);
                }
            }
        }

        return null;
    }

    /**
     * @param array  $config
     * @param string $childName
     *
     * @return ConfigurationProvider
     */
    private function removeItem(array &$config, $childName)
    {
        if (!empty($config['children'])) {
            foreach ($config['children'] as $key => &$configRow) {
                if ($key === $childName) {
                    unset($config['children'][$childName]);
                } elseif (is_array($configRow)) {
                    $this->removeItem($configRow, $childName);
                }
            }
        }

        return $this;
    }

    /**
     * Process configurations to validate and merge
     *
     * @param array $config
     *
     * @return array
     */
    private function processConfiguration(array $config)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        return $processor->processConfiguration($configuration, [Configuration::ROOT_NODE => $config]);
    }

    /**
     * @param null|array $config
     */
    protected function normalizeOptionNames(&$config)
    {
        $normalizeMap = [
            'templates' => [
                'current_as_link'      => 'currentAsLink',
                'current_class'        => 'currentClass',
                'ancestor_class'       => 'ancestorClass',
                'first_class'          => 'firstClass',
                'last_class'           => 'lastClass',
                'root_class'           => 'rootClass',
                'is_dropdown'          => 'isDropdown',
            ],
            'items' => [
                'translate_domain'     => 'translateDomain',
                'translate_parameters' => 'translateParameters',
                'route_parameters'     => 'routeParameters',
                'link_attributes'      => 'linkAttributes',
                'label_attributes'     => 'labelAttributes',
                'children_attributes'  => 'childrenAttributes',
                'display_children'     => 'displayChildren',
            ],
        ];

        foreach ($normalizeMap as $configKey => $optionNameMap) {
            foreach ($config[$configKey] as &$options) {
                foreach ($options as $key => $value) {
                    if (array_key_exists($key, $optionNameMap)) {
                        unset($options[$key]);
                        $options[$optionNameMap[$key]] = $value;
                    }
                }
            }
        }
    }

    /**
     * @return CumulativeConfigLoader
     */
    private function getConfigurationLoader()
    {
        return new CumulativeConfigLoader(
            self::COMPILER_PASS_NAME,
            new YamlCumulativeFileLoader('Resources/config/oro/navigation.yml')
        );
    }
}
