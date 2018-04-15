<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Represents the configuration section builder.
 */
interface ConfigurationSectionInterface
{
    /**
     * Builds the definition of a section configuration.
     *
     * @param NodeBuilder $node
     */
    public function configure(NodeBuilder $node): void;

    /**
     * Checks if this section can be added to the given configuration section.
     *
     * @param string $section Configuration section, e.g. entities.entity, relations.entity, etc.
     *
     * @return bool
     */
    public function isApplicable(string $section): bool;

    /**
     * Injects the configuration settings.
     *
     * @param ConfigurationSettingsInterface $settings
     */
    public function setSettings(ConfigurationSettingsInterface $settings): void;
}
