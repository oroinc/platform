<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter;

use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Defines the configuration parameters recognized by DataGrid.
 */
class Configuration implements ConfigurationInterface
{
    public const DEFAULT_TYPE          = 'field';
    public const DEFAULT_FRONTEND_TYPE = PropertyInterface::TYPE_STRING;

    public const TYPE_KEY       = 'type';
    public const COLUMNS_KEY    = 'columns';
    public const PROPERTIES_KEY = 'properties';

    /** @var array */
    protected $types;

    protected $root;

    /**
     * @param        $types
     * @param string $root
     */
    public function __construct($types, $root)
    {
        $this->types = $types;
        $this->root  = $root;
    }

    #[\Override]
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder($this->root);

        $builder->getRootNode()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->ignoreExtraKeys()
                ->children()
                    ->scalarNode(self::TYPE_KEY)
                        ->defaultValue(self::DEFAULT_TYPE)
                        ->validate()
                        ->ifNotInArray($this->types)
                            ->thenInvalid('Invalid property type "%s"')
                        ->end()
                    ->end()
                    // if "data name" is not specified a field name is used
                    ->scalarNode(PropertyInterface::DATA_NAME_KEY)->end()
                    // just validate type if node exist
                    ->scalarNode(PropertyInterface::FRONTEND_TYPE_KEY)->defaultValue(self::DEFAULT_FRONTEND_TYPE)->end()
                    ->scalarNode('label')->end()
                    ->booleanNode(PropertyInterface::TRANSLATABLE_KEY)->defaultTrue()->end()
                    ->booleanNode('editable')->defaultFalse()->end()
                    ->booleanNode('renderable')->end()
                    ->booleanNode('shortenableLabel')->defaultTrue()->end()
                    ->scalarNode('order')->end()
                    ->booleanNode('required')->end()
                ->end()
            ->end();

        return $builder;
    }
}
