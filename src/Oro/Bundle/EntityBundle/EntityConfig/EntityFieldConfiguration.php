<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for entity scope.
 */
class EntityFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'entity';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('label')
                ->info('`string` enables you to change the label of the field.')
            ->end()
            ->scalarNode('description')
                ->info('`string` enables you to change the description of the field.')
            ->end()
            ->scalarNode('is_total')->end()
            ->scalarNode('is_total_currency')->end()
            ->scalarNode('actualize_owning_side_on_change')
                ->info('`boolean` if set to true, the “Updated At” and “Updated By” fields of the owning entity will ' .
                'be updated on collection item updates. Applicable for ref-many and oneToMany relations only.')
                ->defaultFalse()
            ->end()
        ;
    }
}
