<?php

namespace Oro\Bundle\EntityBundle\Config\Validation;

use Oro\Bundle\EntityConfigBundle\Config\Validation\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for view scope.
 */
class ViewFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'view';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('is_displayable', 'normalized_boolean')
                ->info('`boolean` show on view.')
            ->end()
            ->scalarNode('priority')
                ->info('`integer` priority of field.')
            ->end()
            ->scalarNode('type')
                ->info('`string` type of view.')
                ->example('html')
            ->end()
        ;
    }
}
