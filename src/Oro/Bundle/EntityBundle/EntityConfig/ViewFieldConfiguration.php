<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for view scope.
 */
class ViewFieldConfiguration implements FieldConfigInterface
{
    #[\Override]
    public function getSectionName(): string
    {
        return 'view';
    }

    #[\Override]
    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('is_displayable', 'normalized_boolean')
                ->info('`boolean` show on view.')
                ->defaultTrue()
            ->end()
            ->scalarNode('priority')
                ->info('`integer` priority of field.')
            ->end()
            ->scalarNode('type')
                ->info('`string` type of view.')
                ->example('html')
            ->end()
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` this attribute can be used to prohibit changing the view state (no matter ' .
                    'whether it is enabled or not) for the entity. If TRUE than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
