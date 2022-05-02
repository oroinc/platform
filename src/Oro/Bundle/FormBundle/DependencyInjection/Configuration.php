<?php

namespace Oro\Bundle\FormBundle\DependencyInjection;

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
        $treeBuilder = new TreeBuilder('oro_form');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'wysiwyg_enabled' => ['value' => true, 'type' => 'bool'],
            ]
        );

        $rootNode
            ->children()
                ->arrayNode('html_purifier_modes')
                    ->info('Describes scopes and scope rules for HTMLPurifier')
                    ->useAttributeAsKey('default')
                    ->arrayPrototype()
                        ->info('Collection of scopes that defines the rules for HTMLPurifier')
                        ->children()
                            ->scalarNode('extends')
                                ->info('Extends configuration from selected scope')
                                ->example('default')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('allowed_rel')
                                ->beforeNormalization()
                                ->ifArray()
                                ->then(static function (array $allowedRel) {
                                    return array_fill_keys($allowedRel, true);
                                })
                                ->end()
                                ->info(
                                    'List of allowed forward document relationships in the rel attribute ' .
                                    'for HTMLPurifier.'
                                )
                                ->example(['nofollow', 'alternate'])
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('allowed_iframe_domains')
                                ->info(
                                    'Only these domains will be allowed in iframes ' .
                                    '(in case iframes are enabled in allowed elements)'
                                )
                                ->example(['youtube.com/embed/', 'player.vimeo.com/video/'])
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('allowed_uri_schemes')
                                ->info('Allowed URI schemes for HTMLPurifier')
                                ->example(['http', 'https', 'mailto', 'ftp', 'data', 'tel'])
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('allowed_html_elements')
                                ->info('Allowed elements and attributes for HTMLPurifier')
                                ->arrayPrototype()
                                    ->info('Collection of allowed HTML elements for HTMLPurifier')
                                    ->children()
                                        ->arrayNode('attributes')
                                            ->info('Collection of allowed attributes for described HTML tag')
                                            ->example(['cellspacing', 'cellpadding', 'border', 'align', 'width'])
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->booleanNode('hasClosingTag')
                                            ->info('Is HTML tag has closing end tag or not')
                                            ->defaultTrue()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
