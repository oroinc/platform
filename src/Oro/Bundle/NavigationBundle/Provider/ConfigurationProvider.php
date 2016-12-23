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
    const ROOT_PARAMETER     = 'navigation';
    const COMPILER_PASS_NAME = 'oro_navigation';
    const CACHE_KEY          = 'oro_navigation.configuration_data';

    const MENU_CONFIG_KEY    = 'menu_config';

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
     * Checks if this provider can be used to load configuration
     *
     * @return bool
     */
    public function isApplicable()
    {
        $this->ensureConfigurationLoaded();

        return 0 !== count($this->rawConfiguration);
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

        $configLoader = self::getConfigurationLoader();
        $resources = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (array_key_exists(self::ROOT_PARAMETER, $resource->data)
                && is_array($resource->data[self::ROOT_PARAMETER])
            ) {
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $resource->data[self::ROOT_PARAMETER]);
            }
        }

        // TODO fix const and names
        $this->rawConfiguration[Configuration::ROOT_NODE] = $config[self::MENU_CONFIG_KEY];
        $this->rawConfiguration[Configuration::ROOT_NODE]['oro_navigation_elements'] = $config['navigation_elements'];

        $this->rawConfiguration[Configuration::ROOT_NODE] = $this->processConfiguration($this->rawConfiguration);
        unset($this->rawConfiguration[Configuration::ROOT_NODE]['settings']);

        $this->rawConfiguration['oro_navigation_titles'] = $config['titles'];

        $this->normalizeOptionNames($this->rawConfiguration[Configuration::ROOT_NODE]);

        // TODO add merge_strategy
//        if (array_key_exists(self::MENU_CONFIG_KEY, $config)
//            && array_key_exists('tree', $config[self::MENU_CONFIG_KEY])) {
//            $this->reorganizeTree($config[self::MENU_CONFIG_KEY]['tree']);
//        }

        if ($this->cache instanceof Cache) {
            $this->cache->save(self::CACHE_KEY, $this->rawConfiguration);
        }

        return $this;
    }

    /**
     * @return array
     */
    private function getRawConfiguration()
    {
        if (!$this->isApplicable()) {
            throw new \RuntimeException(sprintf('Navigation configuration was not found.'));
        }

        return $this->rawConfiguration;
    }

    /**
     * Make sure that configuration is loaded
     */
    private function ensureConfigurationLoaded()
    {
        if (count($this->rawConfiguration) === 0) {
            if (!$this->cache instanceof Cache || !$this->cache->contains(self::CACHE_KEY)) {
                $this->loadConfiguration();
            } else {
                $this->rawConfiguration = $this->cache->fetch(self::CACHE_KEY);
            }
        }

        return $this;
    }

    /**
     * @param array $config
     *
     * @return ConfigurationProvider
     */
    private function reorganizeTree(array &$config)
    {
        foreach ($config as $childName => &$childConfig) {
            $childConfig = (array) $childConfig;
            $strategy = array_key_exists('merge_strategy', $childConfig) ? $childConfig['merge_strategy'] : 'append';
            switch ($strategy) {
                case 'append':
                    if (array_key_exists('children', $childConfig)) {
                        $this->reorganizeTree($childConfig['children']);
                    }
                    break;
                case 'move':
                    $this->moveItem($childName, $childConfig);
                    break;
                case 'remove':
                    $config = $this->rawConfiguration[self::MENU_CONFIG_KEY]['tree'];
                    $this->removeItem($config, $childName);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown menu config merge strategy.');
            }
        }

        return $this;
    }

    /**
     * @param array  $config
     * @param string $childName
     *
     * @return array|null
     */
    private function getMenuItemByName(array $config, $childName)
    {
        if (array_key_exists('children', $config) && is_array($config['children'])) {
            foreach ($config['children'] as $key => $configRow) {
                if ($key === $childName) {
                    return $config['children'][$childName];
                }

                return $this->getMenuItemByName($configRow, $childName);
            }
        }

        return null;
    }

    /**
     * @param string $childName
     * @param array  $childConfig
     *
     * @return ConfigurationProvider
     */
    private function moveItem($childName, array &$childConfig)
    {
        $config = $this->rawConfiguration[self::MENU_CONFIG_KEY]['tree'];
        $existingItem = $this->getMenuItemByName($config, $childName);
        if (!empty($existingItem['children'])) {
            $childChildren = array_key_exists('children', $childConfig) ? $childConfig['children'] : [];
            $childConfig['children'] = array_merge($existingItem['children'], $childChildren);

            $this->removeItem($config, $childName);
        }

        return $this;
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

        return $processor->processConfiguration($configuration, $config);
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
    public static function getConfigurationLoader()
    {
        return new CumulativeConfigLoader(
            self::COMPILER_PASS_NAME,
            new YamlCumulativeFileLoader('Resources/config/oro/navigation.yml')
        );
    }
}
