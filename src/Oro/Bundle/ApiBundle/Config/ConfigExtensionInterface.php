<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSectionInterface;

interface ConfigExtensionInterface
{
    /**
     * Returns a list of definitions an entity configuration sections.
     *
     * @return ConfigurationSectionInterface[] [section name => configuration, ...]
     */
    public function getEntityConfigurationSections();

    /**
     * Returns a list callbacks that should be used to build a configuration of a section.
     *
     * @return array [section => [function (NodeBuilder $node), ...], ...]
     *               Where the section is the name/path to a configuration section,
     *               e.g. "entities.entity", "relations.entity.field", "sorters", "filters.field", etc.
     */
    public function getConfigureCallbacks();

    /**
     * Returns a list callbacks that should be used to pre processing of a configuration.
     *
     * @return array [section => [function (array|null $config) : array|null, ...], ...]
     *               Where the section is the name/path to a configuration section,
     *               e.g. "entities.entity", "relations.entity.field", "sorters", "filters.field", etc.
     */
    public function getPreProcessCallbacks();

    /**
     * Returns a list callbacks that should be used to post processing of a configuration.
     *
     * @return array [section => [function (array $config) : array, ...], ...]
     *               Where the section is the name/path to a configuration section,
     *               e.g. "entities.entity", "relations.entity.field", "sorters", "filters.field", etc.
     */
    public function getPostProcessCallbacks();

    /**
     * Returns a list of loaders for an entity configuration sections.
     *
     * @return ConfigLoaderInterface[] [config type => loader, ...]
     */
    public function getEntityConfigurationLoaders();
}
