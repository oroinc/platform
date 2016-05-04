<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DeleteMassActionConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('delete')
            ->canBeDisabled()
            ->end();

        return $builder;
    }
}
