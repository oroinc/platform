<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    #[\Override]
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('oro_test_framework');
        $rootNode = $builder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('install_options')
                    ->children()
                        ->scalarNode('user_name')->end()
                        ->scalarNode('user_email')->end()
                        ->scalarNode('user_firstname')->end()
                        ->scalarNode('user_lastname')->end()
                        ->scalarNode('user_password')->end()
                        ->booleanNode('sample_data')->end()
                        ->scalarNode('organization_name')->end()
                        ->scalarNode('application_url')->end()
                        ->booleanNode('skip_translations')->end()
                        ->integerNode('timeout')
                            ->min(0)
                        ->end()
                        ->scalarNode('language')->end()
                        ->scalarNode('formatting_code')->end()
                    ->end()
                ->end()
                ->arrayNode('test_auth_firewalls')
                    ->info('The list of security firewalls for which test authorization should be enabled.')
                    ->prototype('scalar')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
