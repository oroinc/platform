<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_LAYOUT_PHP_RESOURCE  = 'OroLayoutBundle:Layout/php';
    const DEFAULT_LAYOUT_TWIG_RESOURCE = 'OroLayoutBundle:Layout:div_layout.html.twig';

    const AUTO = 'auto';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('oro_layout');

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

        $configTreeBuilder = new TreeBuilder();
        $configNode = $configTreeBuilder->root('config');
        $configNode->info('Layout theme additional config')->end();
        // Allow extra configuration keys to be present in this configuration node.
        // This is needed to give other bundles ability to declare and add custom configuration.
        $configNode->ignoreExtraKeys(false);

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
                    ->append($configNode)
                ->end()
            ->end();

        $this->appendConfigNodes($configNode);

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
     * @param ArrayNodeDefinition $configNode
     */
    protected function appendConfigNodes($configNode)
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
                        ->prototype('variable')->end()
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

        $widthHeightValidator = function ($value) {
            return !is_null($value) && !is_int($value) && self::AUTO !== $value;
        };

        $imagesNode
            ->children()
                ->arrayNode('types')
                ->useAttributeAsKey('image-type-identifier')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('max_number')->defaultNull()->end()
                            ->arrayNode('dimensions')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('dimensions')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->validate()
                            ->ifTrue(function (array $dimension) {
                                return self::AUTO === $dimension['width'] && self::AUTO === $dimension['height'];
                            })
                            ->thenInvalid('Either width or height can be set to \'auto\', not both.')
                        ->end()
                        ->children()
                            ->scalarNode('width')
                                ->validate()
                                    ->ifTrue($widthHeightValidator)
                                    ->thenInvalid('Width value can be null, \'auto\' or integer only')
                                ->end()
                            ->end()
                            ->scalarNode('height')
                                ->validate()
                                    ->ifTrue($widthHeightValidator)
                                    ->thenInvalid('Height value can be null, \'auto\' or integer only')
                                ->end()
                            ->end()
                            ->arrayNode('options')
                                ->prototype('variable')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        $configNode->append($assetsNode);
        $configNode->append($imagesNode);
        $configNode->append($this->getPageTemplatesNode($treeBuilder));
    }

    /**
     * @param TreeBuilder $treeBuilder
     * @return ArrayNodeDefinition
     */
    protected function getPageTemplatesNode(TreeBuilder $treeBuilder)
    {
        $pageTemplatesNode = $treeBuilder->root('page_templates');
        $pageTemplatesNode
            ->children()
                ->arrayNode('templates')
                    ->info('List of page templates')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('route_name')->cannotBeEmpty()->end()
                            ->scalarNode('key')->cannotBeEmpty()->end()
                            ->scalarNode('label')->cannotBeEmpty()->end()
                            ->scalarNode('description')->defaultNull()->end()
                            ->scalarNode('screenshot')->defaultNull()->end()
                            ->booleanNode('enabled')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('titles')
                    ->useAttributeAsKey('titles')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $pageTemplatesNode;
    }
}
