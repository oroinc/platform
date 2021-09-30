<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Validation;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class that implements ConfigurationInterface
 */
class Configuration implements ConfigurationInterface
{
    private TreeBuilder $configuration;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
    }

    /** @iheritdoc */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        return $this->configuration;
    }
}
