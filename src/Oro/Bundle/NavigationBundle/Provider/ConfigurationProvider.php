<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\NavigationBundle\DependencyInjection\Configuration;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The navigation configuration provider.
 */
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

        if (!array_key_exists(self::MENU_CONFIG_KEY, $config)) {
            $config[self::MENU_CONFIG_KEY] = [];
        }

        if (array_key_exists('tree', $config[self::MENU_CONFIG_KEY])) {
            foreach ($config[self::MENU_CONFIG_KEY]['tree'] as &$configPart) {
                $configPart = $this->getReorganizedTree($configPart);
            }
            unset($configPart);
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
     *
     * @return ConfigurationProvider
     */
    private function ensureConfigurationLoaded()
    {
        if (count($this->rawConfiguration) === 0) {
            $configuration = $this->cache->fetch(self::CACHE_KEY);
            if (false === $configuration) {
                $this->loadConfiguration();
            } else {
                $this->rawConfiguration = $configuration;
            }
        }

        return $this;
    }

    /**
     * @param array $tree
     *
     * @return array
     */
    private function getReorganizedTree(array $tree)
    {
        $newTree = $tree;
        $newTree['children'] = [];

        foreach ($tree['children'] as $childName => &$childData) {
            $childData = is_array($childData) ? $childData : [];
            $this->reorganizeTree($newTree, $newTree, $childName, $childData);
        }

        return $newTree;
    }

    /**
     * @param array      $tree
     * @param array      $treePart
     * @param string     $childName
     * @param array|null $childData
     *
     * @return $this
     */
    private function reorganizeTree(array &$tree, array &$treePart, $childName, array &$childData)
    {
        $data = $childData;
        if (is_array($data)) {
            $existingChildData = $this->getChildAndRemove($tree, $childName);
            if (!empty($existingChildData['children']) && $this->getMergeStrategy($childData) === 'move') {
                $children = array_key_exists('children', $data) ? $data['children'] : [];
                $data['children'] = array_merge($children, $existingChildData['children']);
            }
        }

        $treePart['children'][$childName] = $data;
        if (array_key_exists('children', $childData)) {
            foreach ($childData['children'] as $key => $value) {
                $value = is_array($value) ? $value : [];
                $this->reorganizeTree($tree, $treePart['children'][$childName], $key, $value);
            }
        }

        return $this;
    }

    /**
     * @param array  $tree
     * @param string $childName
     *
     * @return array
     */
    private function getChildAndRemove(array &$tree, $childName)
    {
        if (!array_key_exists('children', $tree)) {
            return null;
        }

        foreach ($tree['children'] as $key => &$child) {
            if ($key === $childName) {
                unset($tree['children'][$key]);
                return $child;
            } elseif (is_array($child)) {
                $result = $this->getChildAndRemove($child, $childName);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * @param array $treeItem
     *
     * @return string
     */
    private function getMergeStrategy(array $treeItem)
    {
        $mergeStrategy = array_key_exists('merge_strategy', $treeItem) ? $treeItem['merge_strategy'] : 'move';
        if ($mergeStrategy !== 'move' && $mergeStrategy !== 'replace') {
            throw new \UnexpectedValueException(sprintf(
                'Invalid "merge_strategy". Merge strategy should be "move" or "replace", but "%s" is given.',
                $mergeStrategy
            ));
        }

        return $mergeStrategy;
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
