<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for grouping scope.
 */
class GroupingEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'grouping';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('groups')
                ->info('`string[]` allows to group entities. An entity can be included in several groups.')
                ->scalarPrototype()->end()
            ->end()
        ;
    }
}
