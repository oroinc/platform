<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_LAYOUT_PHP_RESOURCE  = 'OroLayoutBundle:Layout/php';
    const DEFAULT_LAYOUT_TWIG_RESOURCE = 'OroLayoutBundle:Layout:div_layout.html.twig';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_layout');

        $rootNode
            ->children()
                ->arrayNode('view')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('annotations')
                            ->info('Defines whether @Layout annotation can be used in controllers')
                            ->defaultTrue()
                        ->end()
                    ->end()
                ->end()
            ->end();
        $this->appendTemplatingNodes($rootNode);
        $this->appendThemingNodes($rootNode);

        return $treeBuilder;
    }

    /**
     * Appends config nodes for "templating"
     *
     * @param ArrayNodeDefinition $parentNode
     */
    protected function appendTemplatingNodes(ArrayNodeDefinition $parentNode)
    {
        $treeBuilder = new TreeBuilder();
        $node        = $treeBuilder->root('templating');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('default')->defaultValue('twig')->end()
                ->arrayNode('php')
                    ->canBeDisabled()
                    ->fixXmlConfig('resource')
                    ->children()
                        ->arrayNode('resources')
                            ->addDefaultChildrenIfNoneSet()
                            ->prototype('scalar')->defaultValue(self::DEFAULT_LAYOUT_PHP_RESOURCE)->end()
                            ->example(['MyBundle:Layout/php'])
                            ->validate()
                                ->ifTrue(
                                    function ($v) {
                                        return !in_array(self::DEFAULT_LAYOUT_PHP_RESOURCE, $v);
                                    }
                                )
                                ->then(
                                    function ($v) {
                                        return array_merge([self::DEFAULT_LAYOUT_PHP_RESOURCE], $v);
                                    }
                                )
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('twig')
                    ->canBeDisabled()
                    ->fixXmlConfig('resource')
                    ->children()
                        ->arrayNode('resources')
                            ->addDefaultChildrenIfNoneSet()
                            ->prototype('scalar')->defaultValue(self::DEFAULT_LAYOUT_TWIG_RESOURCE)->end()
                            ->example(['MyBundle:Layout:blocks.html.twig'])
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
     *
     * @param ArrayNodeDefinition $parentNode
     */
    protected function appendThemingNodes(ArrayNodeDefinition $parentNode)
    {
        $treeBuilder = new TreeBuilder();
        $node        = $treeBuilder->root('themes');

        $dataTreeBuilder = new TreeBuilder();
        $dataNode = $dataTreeBuilder->root('data');
        $dataNode->info('Layout theme additional data')->end();

        $node
            ->useAttributeAsKey('theme-identifier')
            ->normalizeKeys(false)
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->info('The label is displayed in the theme management UI. Can be empty for "hidden" themes')
                        ->isRequired()
                    ->end()
                    ->scalarNode('description')
                        ->info('The description is displayed in the theme selection UI. Can be empty')
                    ->end()
                    ->scalarNode('icon')
                        ->info('The icon is displayed in the UI')
                    ->end()
                    ->scalarNode('logo')
                        ->info('The logo image is displayed in the UI')
                    ->end()
                    ->scalarNode('screenshot')
                        ->info('The screenshot image is used in theme management UI for the theme preview')
                    ->end()
                    ->scalarNode('directory')
                        ->info('The directory name where to look up for layout updates. By default theme identifier')
                    ->end()
                    ->scalarNode('parent')
                        ->info('The identifier of the parent theme')
                    ->end()
                    ->arrayNode('groups')
                        ->info('Layout groups for which the theme is applicable')
                        ->example('[main, embedded_forms, frontend]')
                        ->prototype('scalar')->end()
                        ->cannotBeEmpty()
                    ->end()
                    ->append($dataNode)
                ->end()
            ->end();

        $this->appendDataNodes($dataNode);

        $parentNode
            ->append($node)
            ->children()
                ->booleanNode('debug')
                    ->info('Enable layout debug mode. Allows to switch theme using request parameter _theme.')
                    ->defaultValue('%kernel.debug%')
                ->end()
                ->scalarNode('active_theme')
                    ->info('The identifier of the theme that should be used by default')
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $dataNode
     */
    protected function appendDataNodes($dataNode)
    {
        $treeBuilder = new TreeBuilder();
        $assetsNode = $treeBuilder->root('assets');
        $imagesNode = $treeBuilder->root('images');

        $assetsNode
            ->useAttributeAsKey('asset-identifier')
            ->normalizeKeys(false)
            ->prototype('array')
                ->children()
                    ->arrayNode('inputs')
                        ->info('Input assets list')
                        ->prototype('scalar')->end()
                    ->end()
                    ->arrayNode('filters')
                        ->info('Filters to manipulate input assets')
                        ->prototype('scalar')->end()
                    ->end()
                    ->scalarNode('output')
                        ->info('Output asset')
                    ->end()
                ->end()
            ->end();

        $imagesNode
            ->children()
                ->arrayNode('types')
                ->useAttributeAsKey('image-type-identifier')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('dimensions')->defaultNull()->end()
                            ->scalarNode('max_number')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $dataNode->append($assetsNode);
        $dataNode->append($imagesNode);
    }
}
