<?php

namespace Oro\Bundle\DataGridBundle\Extension\Export;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('export')
            ->treatTrueLike(['csv' => ['label' => 'oro.grid.export.csv']])
            ->treatFalseLike([])
            ->treatNullLike([])
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()
                    ->scalarNode('label')
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
