<?php

namespace Oro\Bundle\FilterBundle\Grid\Extension;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const FILTERS_PATH         = '[filters]';
    const COLUMNS_PATH         = '[filters][columns]';
    const DEFAULT_FILTERS_PATH = '[filters][default]';

    /** @var array */
    protected $types;

    /**
     * @param $types
     */
    public function __construct($types)
    {
        $this->types = $types;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('filters')
            ->children()
                ->arrayNode('columns')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->ignoreExtraKeys()
                        ->children()
                            ->scalarNode(FilterUtility::TYPE_KEY)
                                ->isRequired()
                                ->validate()
                                ->ifNotInArray($this->types)
                                    ->thenInvalid('Invalid filter type "%s"')
                                ->end()
                            ->end()
                            ->scalarNode(FilterUtility::DATA_NAME_KEY)->isRequired()->end()
                            ->enumNode(FilterUtility::CONDITION_KEY)
                                ->values(array(FilterUtility::CONDITION_AND, FilterUtility::CONDITION_OR))
                            ->end()
                            ->booleanNode(FilterUtility::BY_HAVING_KEY)->end()
                            ->booleanNode(FilterUtility::ENABLED_KEY)->defaultTrue()->end()
                            ->booleanNode(FilterUtility::VISIBLE_KEY)->defaultTrue()->end()
                            ->booleanNode(FilterUtility::TRANSLATABLE_KEY)->defaultTrue()->end()
                            ->booleanNode(FilterUtility::FORCE_LIKE_KEY)->defaultFalse()->end()
                            ->booleanNode(FilterUtility::CASE_INSENSITIVE_KEY)->defaultTrue()->end()
                            ->variableNode(FilterUtility::VALUE_CONVERSION_KEY)
                                ->validate()
                                ->always(function ($v) {
                                    if (is_string($v) || is_array($v)) {
                                        return $v;
                                    }
                                    throw new \InvalidArgumentException(
                                        'Invalid filter type "%s". Only callbacks are allowed'
                                    );
                                })
                                ->end()
                            ->end()
                            ->integerNode(FilterUtility::MIN_LENGTH_KEY)->min(0)->defaultValue(0)->end()
                            ->integerNode(FilterUtility::MAX_LENGTH_KEY)->min(1)->defaultValue(PHP_INT_MAX)->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('default')
                        ->prototype('array')
                            ->prototype('variable')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
