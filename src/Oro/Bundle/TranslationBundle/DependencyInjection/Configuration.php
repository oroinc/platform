<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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
                ->scalarNode('templating')->defaultValue('@OroTranslation/default.html.twig')->end()
                ->arrayNode('translatable_dictionaries')
                    ->info(
                        'The configuration of Gedmo translatable entities'
                        . ' that should by synchronized with the translator component.'
                        . ' All translation messages for these entities should be in the "entities" domain.'
                    )
                    ->example([
                        'Acme\Bundle\AppBundle\Entity\Country' => [
                            'name' => [
                                'translation_key_prefix' => 'acme_country.',
                                'key_field_name' => 'iso2Code'
                            ]
                        ]
                    ])
                    ->useAttributeAsKey('entity class')
                    ->prototype('array')
                        ->useAttributeAsKey('translatable field name')
                        ->prototype('array')
                            ->children()
                                ->scalarNode('translation_key_prefix')
                                    ->info('The prefix for the translation message key.')
                                    ->cannotBeEmpty()
                                ->end()
                                ->scalarNode('key_field_name')
                                    ->info('The field name where the key is stored.')
                                    ->cannotBeEmpty()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        SettingsBuilder::append($rootNode, ['installed_translation_meta' => ['type' => 'array']]);

        return $treeBuilder;
    }
}
