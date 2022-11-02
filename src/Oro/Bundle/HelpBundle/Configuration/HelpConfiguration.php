<?php

namespace Oro\Bundle\HelpBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Provides schema for configuration that is loaded from "Resources/config/oro/help.yml" files.
 */
class HelpConfiguration implements ConfigurationInterface
{
    public const ROOT_NODE = 'help';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $nodeBuilder = $rootNode->children();

        $this->configureResourcesNodeDefinition($nodeBuilder->arrayNode('resources'));
        $this->configureVendorsNodeDefinition($nodeBuilder->arrayNode('vendors'));
        $this->configureRoutesNodeDefinition($nodeBuilder->arrayNode('routes'));

        return $treeBuilder;
    }

    private function configureResourcesNodeDefinition(ArrayNodeDefinition $resourcesNode)
    {
        $resourcesNode
            ->useAttributeAsKey(true)
            ->beforeNormalization()
                ->always(function (array $resources) {
                    $this->assertKeysAreValidResourceNames($resources);

                    return $resources;
                })
            ->end()
            ->prototype('array')
                ->children()
                    ->scalarNode('server')
                        ->validate()
                            ->ifTrue(function ($value) {
                                return !filter_var($value, FILTER_VALIDATE_URL);
                            })
                            ->thenInvalid('Invalid URL %s.')
                        ->end()
                    ->end()
                    ->scalarNode('prefix')->end()
                    ->scalarNode('alias')->end()
                    ->scalarNode('uri')->end()
                    ->scalarNode('link')
                        ->validate()
                            ->ifTrue(function ($value) {
                                return !filter_var($value, FILTER_VALIDATE_URL);
                            })
                            ->thenInvalid('Invalid URL %s.')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function configureVendorsNodeDefinition(ArrayNodeDefinition $vendorsNode)
    {
        $vendorsNode
            ->useAttributeAsKey(true)
            ->beforeNormalization()
                ->always(function (array $vendors) {
                    $this->assertKeysAreValidVendorNames($vendors);

                    return $vendors;
                })
                ->end()
            ->prototype('array')
                ->children()
                    ->scalarNode('server')->end()
                    ->scalarNode('prefix')->end()
                    ->scalarNode('alias')->end()
                    ->scalarNode('uri')->end()
                    ->scalarNode('link')->end()
                ->end()
            ->end();
    }

    private function configureRoutesNodeDefinition(ArrayNodeDefinition $routesNode)
    {
        $routesNode
            ->useAttributeAsKey(true)
            ->beforeNormalization()
                ->always(function (array $vendors) {
                    $this->assertKeysAreValidVendorNames($vendors);

                    return $vendors;
                })
                ->end()
            ->prototype('array')
                ->children()
                    ->scalarNode('server')->end()
                    ->scalarNode('uri')->end()
                    ->scalarNode('link')->end()
                ->end()
            ->end();
    }

    private function assertKeysAreValidVendorNames(array $vendors)
    {
        foreach (array_keys($vendors) as $vendorName) {
            if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $vendorName)) {
                throw new InvalidConfigurationException(
                    sprintf('Node "vendors" contains invalid vendor name "%s".', $vendorName)
                );
            }
        }
    }

    private function assertKeysAreValidResourceNames(array $resources)
    {
        foreach (array_keys($resources) as $resourceName) {
            if (!preg_match('#^(.*?\\\\Controller\\\\(.+)Controller)(::(.+)Action)?$#', $resourceName)) {
                throw new InvalidConfigurationException(
                    sprintf('Node "resources" contains invalid resource name "%s".', $resourceName)
                );
            }
        }
    }
}
