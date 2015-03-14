<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_LAYOUT_PHP_RESOURCE  = 'OroLayoutBundle:Layout/php';
    const DEFAULT_LAYOUT_TWIG_RESOURCE = 'OroLayoutBundle:Layout:div_layout.html.twig';

    const BASE_THEME_IDENTIFIER = 'base';

    const MAIN_PLATFORM_GROUP = 'main';

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
                    ->info('Defines whether @Layout annotation can be used in controllers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('annotations')->defaultTrue()->end()
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

        $node
            ->useAttributeAsKey('theme-identifier')
            ->normalizeKeys(false)
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->info('Label will be displayed in theme management UI')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->scalarNode('icon')
                        ->info('Icon that will be displayed in the UI')
                    ->end()
                    ->scalarNode('logo')
                        ->info('Logo that will be displayed in the UI')
                    ->end()
                    ->scalarNode('screenshot')
                        ->info('Screenshot for preview, will be displayed in theme management UI')
                    ->end()
                    ->scalarNode('directory')
                        ->info('Directory name where to look up for layout updates. By default theme identifier')
                    ->end()
                    ->scalarNode('parent')
                        ->info('Parent theme identifier')
                        ->defaultValue(self::BASE_THEME_IDENTIFIER)
                    ->end()
                    ->arrayNode('groups')
                        ->info('Layout groups for which it\'s applicable.')
                        ->defaultValue([self::MAIN_PLATFORM_GROUP])
                        ->example('[main, embedded_forms, frontend]')
                        ->prototype('scalar')->end()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();

        $parentNode
            ->append($node)
            ->children()
                ->scalarNode('active_theme')
                    ->cannotBeEmpty()
                    ->defaultValue(self::BASE_THEME_IDENTIFIER)
                ->end()
            ->end()
            ->validate()
                ->always(
                    function ($v) {
                        $v['themes'] = isset($v['themes']) ? $v['themes'] : [];

                        if (empty($v['themes'][self::BASE_THEME_IDENTIFIER])) {
                            $v['themes'][self::BASE_THEME_IDENTIFIER] = [
                                'parent'    => null,
                                'directory' => self::BASE_THEME_IDENTIFIER,
                                'hidden'    => true
                            ];
                        }

                        return $v;
                    }
                )
            ->end();
    }
}
