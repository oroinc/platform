<?php

namespace Oro\Bundle\TagBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for tag scope.
 */
class TagEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'tag';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('enabled', 'normalized_boolean')
                ->info('`boolean` indicates whether the entity can have tags. By default false.')
                ->defaultFalse()
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` can be used to prohibit changing the tag state (regardless of whether it is ' .
                'enabled or not). If TRUE, then the current state cannot be changed. By default false.')
            ->end()
            ->node('enableGridColumn', 'normalized_boolean')
                ->info('`boolean` indicates whether column with tags should appear by default on the grid. ' .
                'If FALSE, it does not appear on the grid, and can be enabled from the grid settings. By default true.')
                ->defaultTrue()
            ->end()
            ->node('enableGridFilter', 'normalized_boolean')
                ->info('`boolean` indicates whether tags filter should appear by default on the grid. If FALSE, it ' .
                'does not appear on the grid, and can be enabled from the filter manager. By default true.')
                ->defaultTrue()
            ->end()
            ->node('enableDefaultRendering', 'normalized_boolean')
                ->info('`boolean` indicates whether to use default rendering of tags in entity view pages. If FALSE ' .
                'tags will not be rendered automatically and allows to use custom rendering logic. By default true.')
                ->defaultTrue()
            ->end()
        ;
    }
}
