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
            ->useAttributeAsKey('name')
            ->prototype('scalar')
            ->end();

        return $builder;
    }
}
