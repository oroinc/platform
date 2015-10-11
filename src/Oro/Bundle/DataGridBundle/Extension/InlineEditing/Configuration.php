<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const BASE_CONFIG_KEY             = 'inline_editing';
    const BEHAVIOUR_DEFAULT_VALUE     = 'enable_selected';
    const BEHAVIOUR_ENABLE_ALL_VALUE  = 'enable_all';
    const ENABLED_CONFIG_PATH         = '[inline_editing][enable]';

    /** @var array */
    protected $types;

    protected $behaviourConfigValues;

    protected $root;

    /**
     * @param string $root
     */
    public function __construct($root)
    {
        $this->root  = $root;
        $this->behaviourConfigValues = [self::BEHAVIOUR_DEFAULT_VALUE, self::BEHAVIOUR_ENABLE_ALL_VALUE];
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root($this->root)
            ->validate()
                ->ifTrue(function($v){ return $v['enable'] == true && empty($v['entity_name']);})
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
                        ->scalarNode('route')->defaultValue('orocrm_account_update')->end()
                        ->scalarNode('http_method')->defaultValue('PATCH')->end()
                        ->scalarNode('headers')->end()
                        ->scalarNode('default_route_parameters')->end()
                        ->scalarNode('query_parameter_names')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
