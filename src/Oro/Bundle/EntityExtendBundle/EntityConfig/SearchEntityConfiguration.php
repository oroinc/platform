<?php

namespace Oro\Bundle\EntityExtendBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for search scope.
 */
class SearchEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'search';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('searchable', 'normalized_boolean')
                ->info('`boolean` indicates what custom entity can be searchable.')
            ->end()
        ;
    }
}
