<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for search scope.
 */
class SearchFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'search';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('searchable', 'normalized_boolean')
                ->info('`boolean` indicates what custom field could be searchable.')
            ->end()
        ;
    }
}
