<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration parameters recognized by this bundle.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_translation');
        $rootNode = $treeBuilder->getRootNode()
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
                ->arrayNode('translation_service')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('apikey')->defaultValue('')->end()
                    ->end()
                ->end()
                ->arrayNode('package_names')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('debug_translator')->defaultFalse()->end()
                ->arrayNode('locales')
                    ->beforeNormalization()
                        ->ifString()
                        ->then(fn ($v) => \preg_split('/\s*,\s*/', $v))
                    ->end()
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->end()
                ->end()
                ->booleanNode('default_required')->defaultTrue()->end()
                ->scalarNode('manager_registry')->defaultValue('doctrine')->end()
                ->scalarNode('templating')->defaultValue('OroTranslationBundle::default.html.twig')->end()
            ->end();

        SettingsBuilder::append($rootNode, ['installed_translation_meta' => ['type' => 'array']]);

        return $treeBuilder;
    }
}
