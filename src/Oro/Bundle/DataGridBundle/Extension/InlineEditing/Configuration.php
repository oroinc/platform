<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const BASE_CONFIG_KEY                   = 'inline_editing';
    const CONFIG_KEY_ENABLE                 = 'enable';
    const BEHAVIOUR_DEFAULT_VALUE           = 'enable_selected';
    const BEHAVIOUR_ENABLE_ALL_VALUE        = 'enable_all';
    const ENABLED_CONFIG_PATH               = '[inline_editing][enable]';
    const INLINE_EDIT_BLACK_LIST_ID         = 'id';
    const INLINE_EDIT_BLACK_LIST_CREATED_AT = 'createdAt';
    const INLINE_EDIT_BLACK_LIST_UPDATED_AT = 'updatedAt';
    const DEFAULT_ROUTE                     = 'oro_datagrid_api_rest_entity_patch';

    /**
     * @var array
     */
    protected $types;

    /**
     * @var array
     */
    protected $behaviourConfigValues;

    /**
     * @var string
     */
    protected $root;

    /**
     * @var array
     */
    protected $inlineEditBlackList;

    /**
     * @param string $root
     */
    public function __construct($root)
    {
        $this->root  = $root;
        $this->behaviourConfigValues = [self::BEHAVIOUR_DEFAULT_VALUE, self::BEHAVIOUR_ENABLE_ALL_VALUE];
        $this->inlineEditBlackList = [
            self::INLINE_EDIT_BLACK_LIST_ID,
            self::INLINE_EDIT_BLACK_LIST_CREATED_AT,
            self::INLINE_EDIT_BLACK_LIST_UPDATED_AT,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root($this->root)
            ->validate()
                ->ifTrue(
                    function ($value) {
                        return $value[self::CONFIG_KEY_ENABLE] == true && empty($value['entity_name']);
                    }
                )
                ->thenInvalid('"entity_name" parameter must be not empty.')
            ->end()
            ->children()
                ->booleanNode('enable')->defaultFalse()->end()
                ->scalarNode('entity_name')->end()
                ->enumNode('behaviour')
                    ->values($this->behaviourConfigValues)
                    ->defaultValue(self::BEHAVIOUR_DEFAULT_VALUE)
                ->end()
                ->scalarNode('plugin')->end()
                ->scalarNode('default_editors')->end()
                ->arrayNode('save_api_accessor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('route')->defaultValue(self::DEFAULT_ROUTE)->end()
                        ->scalarNode('http_method')->defaultValue('PATCH')->end()
                        ->scalarNode('headers')->end()
                        ->scalarNode('default_route_parameters')->end()
                        ->scalarNode('query_parameter_names')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }

    /**
     * @return array
     */
    public function getBlackList()
    {
        return $this->inlineEditBlackList;
    }
}
