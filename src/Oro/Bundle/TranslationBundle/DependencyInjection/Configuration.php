<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
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
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            ['languages' => ['value' => ['en_US', 'fr_FR', 'uk_UA'], 'type' => 'array']]
        );

        return $treeBuilder;
    }
}
