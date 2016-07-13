<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldBlackList;

class Configuration implements ConfigurationInterface
{
    const ENABLED_CONFIG_PATH           = '[inline_editing][enable]';
    const BEHAVIOUR_CONFIG_PATH         = '[inline_editing][behaviour]';

    const BEHAVIOUR_ENABLE_SELECTED     = 'enable_selected';
    const BEHAVIOUR_ENABLE_ALL_VALUE    = 'enable_all';
    const DEFAULT_ROUTE                 = 'oro_api_patch_entity_data';

    const CONFIG_ENABLE_KEY             = 'enable';
    const BASE_CONFIG_KEY               = 'inline_editing';
    const CONFIG_ENTITY_KEY             = 'entity_name';
    const CONFIG_EXTENDED_ENTITY_KEY    = 'extended_entity_name';
    const AUTOCOMPLETE_API_ACCESSOR_KEY = 'autocomplete_api_accessor';
    const CLASS_KEY                     = 'class';
    const EDITOR_KEY                    = 'editor';
    const VIEW_KEY                      = 'view';
    const VIEW_OPTIONS_KEY              = 'view_options';
    const VALUE_FIELD_NAME_KEY          = 'value_field_name';
    const CHOICES_KEY                   = 'choices';

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
     * @param string $root
     */
    public function __construct($root)
    {
        $this->root = $root;
        $this->behaviourConfigValues = [self::BEHAVIOUR_ENABLE_SELECTED, self::BEHAVIOUR_ENABLE_ALL_VALUE];
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
                        return $value[self::CONFIG_ENABLE_KEY] == true && empty($value[self::CONFIG_ENTITY_KEY]);
                    }
                )
                ->thenInvalid(
                    '"' . self::CONFIG_ENTITY_KEY . '" or "'
                    . self::CONFIG_EXTENDED_ENTITY_KEY . '" parameter must be not empty.'
                )
            ->end()
            ->children()
                ->booleanNode('enable')->defaultFalse()->end()
                ->scalarNode(self::CONFIG_ENTITY_KEY)->end()
                ->enumNode('behaviour')
                    ->values($this->behaviourConfigValues)
                    ->defaultValue(self::BEHAVIOUR_ENABLE_ALL_VALUE)
                ->end()
                ->scalarNode('plugin')->end()
                ->scalarNode('default_editors')->end()
                ->arrayNode('save_api_accessor')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('route')->defaultValue(self::DEFAULT_ROUTE)->end()
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
            ->end();

        return $builder;
    }

    /**
     * @return array
     */
    public function getBlackList()
    {
        return EntityFieldBlackList::getValues();
    }
}
