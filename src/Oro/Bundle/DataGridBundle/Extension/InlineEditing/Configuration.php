<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const BASE_CONFIG_KEY          = 'inline_editing';

    /** @var array */
    protected $types;

    protected $root;

    /**
     * @param string $root
     */
    public function __construct($root)
    {
        $this->root  = $root;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root($this->root)
            ->children()
                ->booleanNode('enabled')->end()
                ->scalarNode('behaviour')->end() //  Possible values: enable_all, enable_selected
                ->scalarNode('plugin')->end()
                ->scalarNode('default_editors')->end()
                ->arrayNode('save_api_accessor')->prototype('scalar')->end()
            ->end();

        return $builder;
    }
}
