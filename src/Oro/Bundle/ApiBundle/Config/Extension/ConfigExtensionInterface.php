<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderInterface;

/**
 * The interface for API configuration extensions.
 */
interface ConfigExtensionInterface
{
    /**
     * Returns a list of definitions an entity configuration sections.
     *
     * @return ConfigurationSectionInterface[] [section name => configuration, ...]
     */
    public function getEntityConfigurationSections(): array;

    /**
     * Returns a list callbacks that should be used to build a configuration of a section.
     *
     * @return array [section => [function (NodeBuilder $node), ...], ...]
     *               Where the section is the name/path to a configuration section,
     *               e.g. "entities.entity", "entities.entity.field", "sorters", "filters.field", etc.
     */
    public function getConfigureCallbacks(): array;

    /**
     * Returns a list callbacks that should be used to pre processing of a configuration.
     *
     * @return array [section => [function (array|null $config) : array|null, ...], ...]
     *               Where the section is the name/path to a configuration section,
     *               e.g. "entities.entity", "entities.entity.field", "sorters", "filters.field", etc.
     */
    public function getPreProcessCallbacks(): array;

    /**
     * Returns a list callbacks that should be used to post processing of a configuration.
     *
     * @return array [section => [function (array $config) : array, ...], ...]
     *               Where the section is the name/path to a configuration section,
     *               e.g. "entities.entity", "entities.entity.field", "sorters", "filters.field", etc.
     */
    public function getPostProcessCallbacks(): array;

    /**
     * Returns a list of loaders for an entity configuration sections.
     *
     * @return ConfigLoaderInterface[] [config type => loader, ...]
     */
    public function getEntityConfigurationLoaders(): array;
}
