<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\DependencyInjection\Configuration;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The provider for navigation configuration
 * that is loaded from "Resources/config/oro/navigation.yml" files.
 */
class ConfigurationProvider extends PhpArrayConfigProvider
{
    public const NAVIGATION_ELEMENTS_KEY = 'navigation_elements';
    public const MENU_CONFIG_KEY         = 'menu_config';
    public const TITLES_KEY              = 'titles';

    private const CONFIG_FILE = 'Resources/config/oro/navigation.yml';

    private const NAVIGATION_CONFIG_ROOT = 'navigation';

    /**
     * Gets configuration for the given group.
     *
     * @param string $groupKey The key of a configuration group
     *
     * @return array
     */
    public function getConfiguration(string $groupKey): array
    {
        $config = $this->doGetConfig();

        if (\array_key_exists($groupKey, $config)) {
            return $config[$groupKey];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $config = [];
        $configLoader = new CumulativeConfigLoader(
            'oro_navigation',
            new YamlCumulativeFileLoader(self::CONFIG_FILE)
        );
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (\array_key_exists(self::NAVIGATION_CONFIG_ROOT, $resource->data)) {
                $resourceConfig = $resource->data[self::NAVIGATION_CONFIG_ROOT];
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $resourceConfig);
            }
        }
        if (!\array_key_exists(self::MENU_CONFIG_KEY, $config)) {
            $config[self::MENU_CONFIG_KEY] = [];
        }
        if (\array_key_exists('tree', $config[self::MENU_CONFIG_KEY])) {
            foreach ($config[self::MENU_CONFIG_KEY]['tree'] as &$configPart) {
                $configPart = $this->getReorganizedTree($configPart);
            }
            unset($configPart);
        }

        $processedConfig = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new Configuration(),
            [Configuration::ROOT_NODE => $config]
        );

        unset($processedConfig['settings']);

        $this->normalizeOptionNames($processedConfig[self::MENU_CONFIG_KEY]);

        return $processedConfig;
    }

    /**
     * @param array $tree
     *
     * @return array
     */
    private function getReorganizedTree(array $tree): array
    {
        $newTree = $tree;
        $newTree['children'] = [];
        foreach ($tree['children'] as $childName => &$childData) {
            $childData = \is_array($childData) ? $childData : [];
            $this->reorganizeTree($newTree, $newTree, $childName, $childData);
        }

        return $newTree;
    }

    /**
     * @param array  $tree
     * @param array  $treePart
     * @param string $childName
     * @param array  $childData
     */
    private function reorganizeTree(array &$tree, array &$treePart, string $childName, array &$childData): void
    {
        $data = $childData;
        if (\is_array($data)) {
            $existingChildData = $this->getChildAndRemove($tree, $childName);
            if (!empty($existingChildData['children']) && $this->getMergeStrategy($childData) === 'move') {
                $children = \array_key_exists('children', $data) ? $data['children'] : [];
                $data['children'] = \array_merge($children, $existingChildData['children']);
            }
        }

        $treePart['children'][$childName] = $data;
        if (\array_key_exists('children', $childData)) {
            foreach ($childData['children'] as $key => $value) {
                $value = \is_array($value) ? $value : [];
                $this->reorganizeTree($tree, $treePart['children'][$childName], $key, $value);
            }
        }
    }

    /**
     * @param array  $tree
     * @param string $childName
     *
     * @return array|null
     */
    private function getChildAndRemove(array &$tree, string $childName): ?array
    {
        if (!\array_key_exists('children', $tree)) {
            return null;
        }

        foreach ($tree['children'] as $key => &$child) {
            if ($key === $childName) {
                unset($tree['children'][$key]);

                return $child;
            }
            if (\is_array($child)) {
                $result = $this->getChildAndRemove($child, $childName);
                if (null !== $result) {
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
    private function getMergeStrategy(array $treeItem): string
    {
        $mergeStrategy = \array_key_exists('merge_strategy', $treeItem)
            ? $treeItem['merge_strategy']
            : 'move';
        if ('move' !== $mergeStrategy && 'replace' !== $mergeStrategy) {
            throw new \UnexpectedValueException(\sprintf(
                'Invalid "merge_strategy". Merge strategy should be "move" or "replace", but "%s" is given.',
                $mergeStrategy
            ));
        }

        return $mergeStrategy;
    }

    /**
     * @param array $config
     */
    private function normalizeOptionNames(array &$config): void
    {
        $normalizeMap = [
            'templates' => [
                'current_as_link' => 'currentAsLink',
                'current_class'   => 'currentClass',
                'ancestor_class'  => 'ancestorClass',
                'first_class'     => 'firstClass',
                'last_class'      => 'lastClass',
                'root_class'      => 'rootClass',
                'is_dropdown'     => 'isDropdown'
            ],
            'items'     => [
                'translate_domain'     => 'translateDomain',
                'translate_parameters' => 'translateParameters',
                'route_parameters'     => 'routeParameters',
                'link_attributes'      => 'linkAttributes',
                'label_attributes'     => 'labelAttributes',
                'children_attributes'  => 'childrenAttributes',
                'display_children'     => 'displayChildren'
            ]
        ];

        foreach ($normalizeMap as $configKey => $optionNameMap) {
            foreach ($config[$configKey] as &$options) {
                foreach ($options as $key => $value) {
                    if (\array_key_exists($key, $optionNameMap)) {
                        unset($options[$key]);
                        $options[$optionNameMap[$key]] = $value;
                    }
                }
            }
        }
    }
}
