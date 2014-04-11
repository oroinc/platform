<?php

namespace Oro\Bundle\DashboardBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const UNSPECIFIED_COLUMN = 1;
    const UNSPECIFIED_POSITION = 9999;

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('oro_dashboard')
            ->children()
                ->arrayNode('widgets')
                    ->info('Configuration of widgets')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->ignoreExtraKeys()
                        ->children()
                            ->scalarNode('label')
                                ->info('The label name for widget title')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('icon')
                                ->info('The name of widget icon. Use only icon name without "icon-" prefix')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('route')
                                ->info('The route name of a controller responsible to render a widget')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('route_parameters')
                                ->info('Additional parameters for the route. "widget" parameter is added automatically')
                                ->useAttributeAsKey('name')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->scalarNode('acl')
                                ->info('The ACL ancestor')
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('items')
                                ->info('A list of additional items for "itemized" widgets')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->ignoreExtraKeys()
                                    ->children()
                                        ->scalarNode('label')
                                            ->info('The label name for item')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('icon')
                                            ->info('The name of item icon. Use only icon name without "icon-" prefix')
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->scalarNode('route')
                                            ->info('The redirect route name')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->arrayNode('route_parameters')
                                            ->info('Additional parameters for the route')
                                            ->useAttributeAsKey('name')
                                            ->prototype('scalar')
                                            ->end()
                                        ->end()
                                        ->scalarNode('acl')
                                            ->info('The ACL ancestor')
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->integerNode('position')
                                            ->info('The position in which an item is rendered')
                                            ->cannotBeEmpty()
                                            ->defaultValue(self::UNSPECIFIED_POSITION)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('dashboards')
                    ->info('Configuration of dashboards')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')
                                ->info('The label name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('twig')
                                ->info(
                                    'The name of TWIG template.'
                                    . ' Default template is "OroDashboardBundle:Index:default.html.twig"'
                                )
                                ->cannotBeEmpty()
                            ->end()
                            ->arrayNode('widgets')
                                ->info('A list of widgets')
                                ->useAttributeAsKey('name')
                                ->prototype('array')
                                    ->ignoreExtraKeys()
                                    ->children()
                                        ->arrayNode('layout_position')
                                            ->info('The position in dashboard layout in which a widget is rendered')
                                            ->cannotBeEmpty()
                                            ->defaultValue(array(self::UNSPECIFIED_COLUMN, self::UNSPECIFIED_POSITION))
                                            ->validate()
                                                ->always(
                                                    function ($value) {
                                                        if (count($value) != 2) {
                                                            throw new \Exception(
                                                                'Value should contain at two elements.'
                                                            );
                                                        }
                                                        $value = array_values($value);
                                                        if ($value[0] < 0) {
                                                            throw new \Exception(
                                                                'First element should be greater or equal than 0.'
                                                            );
                                                        }
                                                        return array_values($value);
                                                    }
                                                )
                                            ->end()
                                            ->prototype('integer')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_dashboard')
                    ->info('The name of a dashboard which is displayed by default')
                    ->defaultValue('main')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
