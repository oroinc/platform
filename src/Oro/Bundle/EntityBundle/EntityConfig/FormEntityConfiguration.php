<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for form scope.
 */
class FormEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'form';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('form_type')
                ->info('`string` form type for a specific entity.')
            ->end()
            ->arrayNode('form_options')
                ->info('`array` form options for a specific entity.')
                ->prototype('variable')->end()
            ->end()
            ->scalarNode('grid_name')
                ->info('`string` name of grid of the entity. Examples: ‘users-select-grid’, ‘contacts-select-grid’, ' .
                '‘customer-customers-select-grid’.')
            ->end()
        ;
    }
}
