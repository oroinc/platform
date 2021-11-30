<?php

namespace Oro\Bundle\ActivityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for grid scope.
 */
class GridEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'grid';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('context')
                ->info('`string` a grid name that used for rendering entity context.')
            ->end()
        ;
    }
}
