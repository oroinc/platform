<?php

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_ADAPTER = 'crowdin';
    const DEFAULT_CROWDIN_API_URL = 'https://api.crowdin.com/api';
    const DEFAULT_PROXY_API_URL = 'http://translations.orocrm.com/api';

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
                            ->defaultValue(['jsmessages', 'validators'])
                            ->prototype('scalar')
                            ->end()
                        ->end()
                        ->booleanNode('debug')
                            ->defaultTrue()
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
                                    ->defaultValue(self::DEFAULT_CROWDIN_API_URL)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('oro_service')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('endpoint')->defaultValue(self::DEFAULT_PROXY_API_URL)->end()
                                ->scalarNode('key')->defaultValue('')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_api_adapter')->defaultValue(self::DEFAULT_ADAPTER)->end()
                ->scalarNode('debug_translator')->defaultFalse()->end()
            ->end();

        SettingsBuilder::append(
            $rootNode,
            [
                'installed_translation_meta' => ['type' => 'array']
            ]
        );

        return $treeBuilder;
    }
}
