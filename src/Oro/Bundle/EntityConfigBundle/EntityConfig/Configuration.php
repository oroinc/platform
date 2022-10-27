<?php

namespace Oro\Bundle\EntityConfigBundle\EntityConfig;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class that implements ConfigurationInterface
 */
class Configuration implements ConfigurationInterface
{
    public function __construct(private TreeBuilder $configuration)
    {
    }

    /** @iheritdoc */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        return $this->configuration;
    }
}
