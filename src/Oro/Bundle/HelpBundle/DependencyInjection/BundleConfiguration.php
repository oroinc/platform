<?php

namespace Oro\Bundle\HelpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class BundleConfiguration extends AbstractConfiguration
{
    const CONFIG_ROOT_NODE = 'help';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(self::CONFIG_ROOT_NODE);

        $nodeBuilder = $rootNode->children();

        $this->configureResourcesNodeDefinition($nodeBuilder->arrayNode('resources'));
        $this->configureVendorsNodeDefinition($nodeBuilder->arrayNode('vendors'));
        $this->configureRoutesNodeDefinition($nodeBuilder->arrayNode('routes'));

        return $treeBuilder;
    }
}
