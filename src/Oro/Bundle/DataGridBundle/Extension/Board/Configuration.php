<?php

namespace Oro\Bundle\DataGridBundle\Extension\Board;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\DataGridBundle\Entity\Manager\AppearanceTypeManager;
use Oro\Bundle\DataGridBundle\Extension\Board\Processor\DefaultProcessor;

class Configuration implements ConfigurationInterface
{
    const GROUP_KEY = 'group_by';
    const GROUP_PROPERTY_KEY = 'property';
    const GROUP_PROPERTY_VALUE_KEY = 'value_field_name';
    const GROUP_PROPERTY_ORDER_BY = 'order_by';

    const LABEL_KEY = 'label';
    const ICON_KEY = 'icon';

    const ACL_RESOURCE_KEY = 'acl_resource';
    const DEFAULT_COLUMN_KEY = 'default_column';

    const PLUGIN_KEY = 'plugin';
    const BOARD_VIEW_KEY = 'board_view';
    const CARD_VIEW_KEY = 'card_view';
    const HEADER_VIEW_KEY = 'column_header_view';
    const COLUMN_VIEW_KEY = 'column_view';
    const DEFAULT_PLUGIN = 'orodatagrid/js/app/plugins/grid-component/board-appearance-plugin';
    const DEFAULT_BOARD_VIEW = 'orodatagrid/js/app/views/board/board-view';
    const DEFAULT_CARD_VIEW = 'orodatagrid/js/app/views/board/card-view';
    const DEFAULT_HEADER_VIEW = 'orodatagrid/js/app/views/board/column-header-view';
    const DEFAULT_COLUMN_VIEW = 'orodatagrid/js/app/views/board/column-view';

    const DEFAULT_TRANSITION_CLASS = 'orodatagrid/js/app/transitions/update-main-property-transition';
    const DEFAULT_ROUTE = 'oro_api_patch_entity_data';
    const TRANSITION_KEY = 'default_transition';
    const TRANSITION_CLASS_KEY = 'class';
    const TRANSITION_API_ACCESSOR_KEY = 'save_api_accessor';
    const DEFAULT_TRANSITION_API_ACCESSOR_CLASS = 'oroui/js/tools/api-accessor';
    const TRANSITION_PARAMS_KEY = 'params';

    const TOOLBAR_KEY = 'toolbar';
    const ADDITIONAL_KEY = 'additional';

    const PROCESSOR_KEY = 'processor';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('board')
                ->children()
                    ->scalarNode(static::LABEL_KEY)->defaultValue('oro.datagrid.appearance.board')->end()
                    ->scalarNode(static::ICON_KEY)->defaultValue('icon-th')->end()
                    ->scalarNode(static::PROCESSOR_KEY)->defaultValue(DefaultProcessor::NAME)->end()
                    ->scalarNode(static::ACL_RESOURCE_KEY)->end()
                    ->arrayNode(static::GROUP_KEY)
                        ->children()
                            ->scalarNode(static::GROUP_PROPERTY_KEY)->cannotBeEmpty()->end()
                            ->scalarNode(static::GROUP_PROPERTY_VALUE_KEY)->end()
                            ->arrayNode(static::GROUP_PROPERTY_ORDER_BY)
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode(static::DEFAULT_COLUMN_KEY)->end()
                    ->scalarNode(static::PLUGIN_KEY)->defaultValue(static::DEFAULT_PLUGIN)->end()
                    ->scalarNode(static::BOARD_VIEW_KEY)->defaultValue(static::DEFAULT_BOARD_VIEW)->end()
                    ->scalarNode(static::CARD_VIEW_KEY)->defaultValue(static::DEFAULT_CARD_VIEW)->end()
                    ->scalarNode(static::HEADER_VIEW_KEY)->defaultValue(static::DEFAULT_HEADER_VIEW)->end()
                    ->scalarNode(static::COLUMN_VIEW_KEY)->defaultValue(static::DEFAULT_COLUMN_VIEW)->end()
                    ->arrayNode(static::TRANSITION_KEY)
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode(static::TRANSITION_CLASS_KEY)
                                ->defaultValue(static::DEFAULT_TRANSITION_CLASS)->end()
                            ->arrayNode(static::TRANSITION_PARAMS_KEY)
                                ->prototype('variable')->end()
                            ->end()
                            ->arrayNode(static::TRANSITION_API_ACCESSOR_KEY)
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('class')
                                        ->defaultValue(static::DEFAULT_TRANSITION_API_ACCESSOR_CLASS)
                                    ->end()
                                    ->scalarNode('route')->defaultValue(static::DEFAULT_ROUTE)->end()
                                    ->scalarNode('http_method')->defaultValue('PATCH')->end()
                                    ->scalarNode('headers')->end()
                                    ->arrayNode('default_route_parameters')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('className')->defaultValue(null)->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('query_parameter_names')
                                        ->prototype('scalar')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode(static::TOOLBAR_KEY)
                        ->defaultValue([])
                        ->prototype('variable')->end()
                    ->end()
                    ->arrayNode(static::ADDITIONAL_KEY)
                        ->defaultValue([])
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
