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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('oro_form');

        SettingsBuilder::append(
            $rootNode,
            [
                'wysiwyg_enabled' => ['value' => true, 'type' => 'bool'],
            ]
        );

        $rootNode
            ->children()
                ->arrayNode('purifier')
                    ->arrayPrototype()
                        ->info('Collection of scopes that defines the rules for HTMLPurifier')
                        ->children()
                            ->enumNode('html_purifier_mode')
                                ->values(['strict', 'extended', 'disabled'])
                                ->defaultValue('strict')
                                ->info(
                                    "\"strict\" - filter html elements and attributes by white list. " .
                                    "Style and iframe elements are not allowed\n" .
                                    "\"extended\" - same as strict but style and iframe elements are allowed\n" .
                                    "\"disabled\" - HTML Purifier is disabled completely"
                                )
                            ->end()
                            ->arrayNode('html_purifier_iframe_domains')
                                ->scalarPrototype()->end()
                                ->info(
                                    'Only these domains will be allowed in iframes ' .
                                    '(in case iframes are enabled in extended mode)'
                                )
                                ->example(['youtube.com/embed/', 'player.vimeo.com/video/'])
                            ->end()
                            ->arrayNode('html_purifier_uri_schemes')
                                ->scalarPrototype()->end()
                                ->info('Allowed URI schemes for HTMLPurifier')
                                ->example(['http', 'https', 'mailto', 'ftp', 'data', 'tel'])
                            ->end()
                            ->arrayNode('html_allowed_elements')
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
