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

        $rootNode->children()
            ->arrayNode('wysiwyg')->children()
                ->enumNode('html_purifier_mode')
                    ->values(['strict', 'extended', 'disabled'])
                    ->defaultValue('strict')
                    ->info(
                        "strict - filter html elements and attributes by white list. " .
                        "Style and iframe elements are not allowed\n" .
                        "extended - same as strict but style and iframe elements are allowed\n" .
                        "disabled - HTML Purifier is disabled completely"
                    )
                ->end()
                ->arrayNode('html_purifier_iframe_domains')
                    ->prototype('scalar')->end()
                    ->info(
                        'only these domains will be allowed in iframes ' .
                        '(in case iframes are enabled in extended mode)'
                    )
                ->end()
                ->arrayNode('html_purifier_uri_schemes')
                    ->prototype('scalar')->end()
                    ->info(
                        'allowed URI schemes for HTMLPurifier'
                    )
                ->end()
                ->arrayNode('html_allowed_elements')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('attributes')
                                ->prototype('scalar')->end()
                            ->end()
                            ->booleanNode('hasClosingTag')->defaultTrue()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()->end();

        return $treeBuilder;
    }
}
