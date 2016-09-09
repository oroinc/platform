<?php

namespace Oro\Bundle\ApiBundle\Config\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

interface ConfigurationSectionInterface
{
    /**
     * Builds the definition of a section configuration.
     *
     * @param NodeBuilder $node
     */
    public function configure(NodeBuilder $node);

    /**
     * Checks if section can be added to the given configuration section
     *
     * @param string $section Configuration section, e.g. entities.entity, relations.entity, etc.
     *
     * @return bool
     */
    public function isApplicable($section);

    /**
     * Injects the configuration settings
     *
     * @param ConfigurationSettingsInterface $settings
     */
    public function setSettings(ConfigurationSettingsInterface $settings);
}
