<?php

namespace Oro\Bundle\EntityMergeBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for merge scope.
 */
class MergeEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'merge';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->variableNode('cast_method')
                ->info('`string` options for rendering entity as string in the UI. Method of entity to cast ' .
                'object to string. If these options are empty __toString will be used (if it’s available).')
            ->end()
            ->scalarNode('template')
                ->info('`string` a twig template to render object as string.')
            ->end()
            ->node('enable', 'normalized_boolean')
                ->info('`boolean` enables merge for this entity.')
            ->end()
            ->scalarNode('max_entities_count')
                ->info('`integer` the max count of entities to merge.')
            ->end()
        ;
    }
}
