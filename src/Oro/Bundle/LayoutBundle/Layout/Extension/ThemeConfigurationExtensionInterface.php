<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The interface for classes that provide definition of additional sections(s) to "config" section
 * of "Resources/views/layouts/{folder}/theme.yml" file.
 */
interface ThemeConfigurationExtensionInterface
{
    /**
     * Gets names of files that can be used to define additional config sections.
     * These files are optional and the same configuration can be defined in
     * "Resources/views/layouts/{folder}/theme.yml" file.
     *
     * @return string[]
     */
    public function getConfigFileNames(): array;

    /**
     * Adds definition of additional sections(s) to "config" section.
     */
    public function appendConfig(NodeBuilder $configNode);
}
