<?php

namespace Oro\Bundle\EntityConfigBundle\EntityConfig;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Extends entity config validation configuration
 */
interface ConfigInterface
{
    /**
     * Gets a name of config section.
     */
    public function getSectionName(): string;

    /**
     * Sets a configuration of section config.
     */
    public function configure(NodeBuilder $nodeBuilder): void;
}
