<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const string DEFAULT_LAYOUT_TWIG_RESOURCE = '@OroLayout/Layout/div_layout.html.twig';

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_layout');
        $rootNode    = $treeBuilder->getRootNode();

        SettingsBuilder::append($rootNode, [
            'development_settings_feature_enabled' => [
                'value' => '%kernel.debug%'
            ],
            'debug_block_info' => [
                'value' => false
            ],
            'debug_developer_toolbar' => [
                'value' => true
            ],
        ]);

        $rootNode
            ->children()
                ->arrayNode('view')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('attributes')
                            ->info('Defines whether #[Layout()] attribute can be used in controllers')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('enabled_themes')
                    ->prototype('scalar')->cannotBeEmpty()->end()
                    ->info('List of enabled themes')
                    ->defaultValue([])
                ->end()
            ->end();

        $this->appendTemplatingNodes($rootNode);
        $this->appendThemingNodes($rootNode);

        return $treeBuilder;
    }

    /**
     * Appends config nodes for "templating"
     */
    protected function appendTemplatingNodes(ArrayNodeDefinition $parentNode): void
    {
        $treeBuilder = new TreeBuilder('templating');
        $node        = $treeBuilder->getRootNode();

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('default')->defaultValue('twig')->end()
                ->arrayNode('twig')
                    ->addDefaultsIfNotSet()
                    ->fixXmlConfig('resource')
                    ->children()
                        ->arrayNode('resources')
                            ->addDefaultChildrenIfNoneSet()
                            ->prototype('scalar')->defaultValue(self::DEFAULT_LAYOUT_TWIG_RESOURCE)->end()
                            ->example(['@My/Layout/blocks.html.twig'])
                            ->validate()
                                ->ifTrue(
                                    function ($v) {
                                        return !in_array(self::DEFAULT_LAYOUT_TWIG_RESOURCE, $v);
                                    }
                                )
                                ->then(
                                    function ($v) {
                                        return array_merge([self::DEFAULT_LAYOUT_TWIG_RESOURCE], $v);
                                    }
                                )
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $parentNode->append($node);
    }

    /**
     * Appends config nodes for "themes"
     */
    protected function appendThemingNodes(ArrayNodeDefinition $parentNode): void
    {
        $parentNode
            ->children()
                ->booleanNode('debug')
                    ->info('Enable layout debug mode. Allows to switch theme using request parameter _theme.')
                    ->defaultValue('%kernel.debug%')
                ->end()
                ->scalarNode('active_theme')
                    ->info('The identifier of the theme that should be used by default')
                ->end()
                ->arrayNode('inherited_theme_options')
                    ->info('List of inherited theme options or theme config options.')
                    ->example(['svg_icons_support', 'config.icons'])
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
            ->end();
    }
}
