<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\EntityFieldBlackList;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Describes the configuration tree for the inline datagrid editing feature.
 */
class Configuration implements ConfigurationInterface
{
    public const ENABLED_CONFIG_PATH           = '[inline_editing][enable]';
    public const BEHAVIOUR_CONFIG_PATH         = '[inline_editing][behaviour]';

    public const BEHAVIOUR_ENABLE_SELECTED     = 'enable_selected';
    public const BEHAVIOUR_ENABLE_ALL_VALUE    = 'enable_all';
    public const DEFAULT_ROUTE                 = 'oro_api_patch_entity_data';

    public const CONFIG_ENABLE_KEY             = 'enable';
    public const BASE_CONFIG_KEY               = 'inline_editing';
    public const CONFIG_ACL_KEY                = 'acl_resource';
    public const CONFIG_ENTITY_KEY             = 'entity_name';
    public const AUTOCOMPLETE_API_ACCESSOR_KEY = 'autocomplete_api_accessor';
    public const SAVE_API_ACCESSOR_KEY         = 'save_api_accessor';
    public const CLASS_KEY                     = 'class';
    public const EDITOR_KEY                    = 'editor';
    public const VIEW_KEY                      = 'view';
    public const VIEW_OPTIONS_KEY              = 'view_options';
    public const VALUE_FIELD_NAME_KEY          = 'value_field_name';
    public const CHOICES_KEY                   = 'choices';

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

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder($this->root);

        $builder->getRootNode()
            ->validate()
                ->ifTrue(
                    function ($value) {
                        return $value[self::CONFIG_ENABLE_KEY] == true && empty($value[self::CONFIG_ENTITY_KEY]);
                    }
                )
                ->thenInvalid(
                    '"entity_name" or "extended_entity_name" parameter must be not empty.'
                )
            ->end()
            ->children()
                ->booleanNode('enable')->defaultFalse()->end()
                ->scalarNode(self::CONFIG_ACL_KEY)->end()
                ->scalarNode(self::CONFIG_ENTITY_KEY)->end()
                ->enumNode('behaviour')
                    ->values($this->behaviourConfigValues)
                    ->defaultValue(self::BEHAVIOUR_ENABLE_ALL_VALUE)
                ->end()
                ->booleanNode('mobile_enabled')->defaultFalse()->end()
                ->arrayNode('cell_editor')
                    ->children()
                        ->scalarNode('component')->end()
                    ->end()
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
