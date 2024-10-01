<?php

namespace Oro\Bundle\SanitizeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration of settings related to database sanitization.
 */
class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'oro_sanitize';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('entity_config_connection')
                    ->info("Entity config's doctrine connection name")
                    ->defaultNull()
                ->end()
                ->scalarNode('custom_email_domain')
                    ->info("A custom email domain used for sanitizing email fields")
                    ->example("example.com")
                    ->validate()
                        ->ifTrue(
                            function ($value) {
                                return $value !== null
                                    && false === filter_var($value, \FILTER_VALIDATE_DOMAIN, \FILTER_FLAG_HOSTNAME);
                            }
                        )
                        ->thenInvalid('Domains are allowed only')
                    ->end()
                    ->defaultNull()
                ->end()
                ->scalarNode('generic_phone_mask')
                    ->info("Generic phone mask for sanitization")
                    ->defaultValue('(XXX) XXX-XXXX')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
