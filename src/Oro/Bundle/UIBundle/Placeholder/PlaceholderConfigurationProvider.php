<?php

namespace Oro\Bundle\UIBundle\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\PlaceholderConfiguration as Config;
use Oro\Component\Config\Cache\PhpArrayConfigProvider;
use Oro\Component\Config\Loader\CumulativeConfigProcessorUtil;
use Oro\Component\Config\Loader\Factory\CumulativeConfigLoaderFactory;
use Oro\Component\Config\ResourcesContainerInterface;

/**
 * The provider for placeholders configuration
 * that is loaded from "Resources/config/oro/placeholders.yml" files.
 */
class PlaceholderConfigurationProvider extends PhpArrayConfigProvider
{
    private const CONFIG_FILE = 'Resources/config/oro/placeholders.yml';

    /**
     * Gets placeholder configuration.
     *
     * @param string $placeholderName
     *
     * @return string[]|null
     */
    public function getPlaceholderItems(string $placeholderName): ?array
    {
        $config = $this->doGetConfig();

        return $config[Config::PLACEHOLDERS_NODE][$placeholderName] ?? null;
    }

    /**
     * Gets placeholder item configuration.
     */
    public function getItemConfiguration(string $itemName): ?array
    {
        $config = $this->doGetConfig();

        return $config[Config::ITEMS_NODE][$itemName] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    protected function doLoadConfig(ResourcesContainerInterface $resourcesContainer)
    {
        $placeholders = [];
        $items = [];
        $configLoader = CumulativeConfigLoaderFactory::create('oro_placeholders', self::CONFIG_FILE);
        $resources = $configLoader->load($resourcesContainer);
        foreach ($resources as $resource) {
            if (!empty($resource->data[Config::ROOT_NODE])) {
                $config = $resource->data[Config::ROOT_NODE];
                if (!empty($config[Config::PLACEHOLDERS_NODE])) {
                    $this->ensurePlaceholdersCompleted($config[Config::PLACEHOLDERS_NODE]);
                    $placeholders[] = $config[Config::PLACEHOLDERS_NODE];
                }
                if (!empty($config[Config::ITEMS_NODE])) {
                    $items[] = $config[Config::ITEMS_NODE];
                }
            }
        }
        if ($placeholders) {
            $placeholders = \array_replace_recursive(...$placeholders);
        }
        if ($items) {
            $items = \array_replace_recursive(...$items);
        }

        return $this->processConfiguration($placeholders, $items);
    }

    /**
     * Makes sure the placeholder's array does not contains gaps
     *
     * For example 'items' attribute should exist for each placeholder
     * even if there are no any items there
     *
     * it is required for correct merging of placeholders
     * if we do not do this the newly loaded placeholder without 'items' attribute removes
     * already loaded items
     */
    private function ensurePlaceholdersCompleted(array &$placeholders)
    {
        $names = \array_keys($placeholders);
        foreach ($names as $name) {
            if (!isset($placeholders[$name][Config::ITEMS_NODE])) {
                $placeholders[$name][Config::ITEMS_NODE] = [];
            }
        }
    }

    private function processConfiguration(array $placeholders, array $items): array
    {
        $processedConfig = CumulativeConfigProcessorUtil::processConfiguration(
            self::CONFIG_FILE,
            new Config(),
            [[Config::PLACEHOLDERS_NODE => $placeholders, Config::ITEMS_NODE => $items]]
        );

        $result = [];
        foreach ($processedConfig[Config::PLACEHOLDERS_NODE] as $placeholderName => $placeholderConfig) {
            $placeholderItems = $placeholderConfig[Config::ITEMS_NODE];
            if ($placeholderItems) {
                $result[Config::PLACEHOLDERS_NODE][$placeholderName] = $placeholderItems;
            }
        }
        foreach ($processedConfig[Config::ITEMS_NODE] as $itemName => $itemConfig) {
            if ($itemConfig) {
                $result[Config::ITEMS_NODE][$itemName] = $itemConfig;
            }
        }

        return $result;
    }
}
