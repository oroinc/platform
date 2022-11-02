<?php

namespace Oro\Bundle\NavigationBundle\Configuration;

use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * The provider for navigation configuration
 * that is loaded from "Resources/config/oro/navigation.yml" files.
 */
class ConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/navigation.yml';

    private const NAVIGATION_ELEMENTS = 'navigation_elements';
    private const MENU_CONFIG         = 'menu_config';
    private const TITLES              = 'titles';
    private const TREE                = 'tree';
    private const ITEMS               = 'items';
    private const TEMPLATES           = 'templates';
    private const CHILDREN            = 'children';

    public function getMenuTree(): array
    {
        $config = $this->doGetConfig();

        $menuConfig = $config[self::MENU_CONFIG];
        if (!array_key_exists(self::TREE, $menuConfig)) {
            return [];
        }

        return $menuConfig[self::TREE];
    }

    public function getMenuItems(): array
    {
        $config = $this->doGetConfig();

        $menuConfig = $config[self::MENU_CONFIG];
        if (!array_key_exists(self::ITEMS, $menuConfig)) {
            return [];
        }

        return $menuConfig[self::ITEMS];
    }

    public function getMenuTemplates(): array
    {
        $config = $this->doGetConfig();

        $menuConfig = $config[self::MENU_CONFIG];
        if (!array_key_exists(self::TEMPLATES, $menuConfig)) {
            return [];
        }

        return $menuConfig[self::TEMPLATES];
    }

    public function getNavigationElements(): array
    {
        $config = $this->doGetConfig();
        if (!isset($config[self::NAVIGATION_ELEMENTS])) {
            return [];
        }

        return $config[self::NAVIGATION_ELEMENTS];
    }

    public function getTitle(string $route): ?string
    {
        $config = $this->doGetConfig();
        if (!isset($config[self::TITLES])) {
            return null;
        }

        $titles = $config[self::TITLES];
        if (array_key_exists($route, $titles)) {
            return $titles[$route];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $config = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_navigation', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (\array_key_exists(NavigationConfiguration::ROOT_NODE, $resource->data)) {
                $resourceConfig = $resource->data[NavigationConfiguration::ROOT_NODE];
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $resourceConfig);
            }
        }
        if (!\array_key_exists(self::MENU_CONFIG, $config)) {
            $config[self::MENU_CONFIG] = [];
        }
        if (\array_key_exists(self::TREE, $config[self::MENU_CONFIG])) {
            foreach ($config[self::MENU_CONFIG][self::TREE] as &$configPart) {
                $configPart = $this->getReorganizedTree($configPart);
            }
            unset($configPart);
        }

        $processedConfig = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new NavigationConfiguration(),
            [NavigationConfiguration::ROOT_NODE => $config]
        );

        $this->normalizeOptionNames($processedConfig[self::MENU_CONFIG]);

        return $processedConfig;
    }

    private function getReorganizedTree(array $tree): array
    {
        $newTree = $tree;
        $newTree[self::CHILDREN] = [];
        foreach ($tree[self::CHILDREN] as $childName => &$childData) {
            $childData = \is_array($childData) ? $childData : [];
            $this->reorganizeTree($newTree, $newTree, $childName, $childData);
        }

        return $newTree;
    }

    private function reorganizeTree(array &$tree, array &$treePart, string $childName, array &$childData): void
    {
        $data = $childData;
        if (\is_array($data)) {
            $existingChildData = $this->getChildAndRemove($tree, $childName);
            if (!empty($existingChildData[self::CHILDREN]) && $this->getMergeStrategy($childData) === 'move') {
                $children = \array_key_exists(self::CHILDREN, $data) ? $data[self::CHILDREN] : [];
                $data[self::CHILDREN] = \array_merge($children, $existingChildData[self::CHILDREN]);
            }
        }

        $treePart[self::CHILDREN][$childName] = $data;
        if (\array_key_exists(self::CHILDREN, $childData)) {
            foreach ($childData[self::CHILDREN] as $key => $value) {
                $value = \is_array($value) ? $value : [];
                $this->reorganizeTree($tree, $treePart[self::CHILDREN][$childName], $key, $value);
            }
        }
    }

    private function getChildAndRemove(array &$tree, string $childName): ?array
    {
        if (!\array_key_exists(self::CHILDREN, $tree)) {
            return null;
        }

        foreach ($tree[self::CHILDREN] as $key => &$child) {
            if ($key === $childName) {
                unset($tree[self::CHILDREN][$key]);

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

    private function normalizeOptionNames(array &$config): void
    {
        $normalizeMap = [
            self::TEMPLATES => [
                'current_as_link' => 'currentAsLink',
                'current_class'   => 'currentClass',
                'ancestor_class'  => 'ancestorClass',
                'first_class'     => 'firstClass',
                'last_class'      => 'lastClass',
                'root_class'      => 'rootClass',
                'is_dropdown'     => 'isDropdown'
            ],
            self::ITEMS     => [
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
