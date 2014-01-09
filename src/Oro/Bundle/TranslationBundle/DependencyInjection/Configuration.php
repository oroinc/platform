<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_ADAPTER = 'crowdin';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_translation')
            ->children()
                ->arrayNode('js_translation')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('domains')
                            ->requiresAtLeastOneElement()
                            ->defaultValue(array('jsmessages', 'validators'))
                            ->prototype('scalar')
                            ->end()
                        ->end()
                        ->booleanNode('debug')
                            ->defaultValue('%kernel.debug%')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('crowdin')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('endpoint')
                                    ->defaultValue('http://api.crowdin.net/api')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('oro_service')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('endpoint')->defaultValue('http://proxy.dev/api')->end()
                                ->scalarNode('key')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_api_adapter')->defaultValue(self::DEFAULT_ADAPTER)->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            ['available_translations' => ['type' => 'array']]
        );

        return $treeBuilder;
    }
}
