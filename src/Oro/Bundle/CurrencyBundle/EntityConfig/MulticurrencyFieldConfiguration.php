<?php

namespace Oro\Bundle\CurrencyBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for multicurrency scope.
 */
class MulticurrencyFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'multicurrency';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('target')
                ->info('`string` The name of virtual field.')
            ->end()
            ->scalarNode('virtual_field')
                ->info('`string` This attribute is used to retrieve the label to be used for virtual field target.')
            ->end()
        ;
    }
}
