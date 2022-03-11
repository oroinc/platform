<?php

namespace Oro\Bundle\EntityConfigBundle\EntityConfig;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for attribute scope.
 */
class AttributeEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'attribute';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->node('has_attributes', 'normalized_boolean')
                ->info('`boolean` is used to enable the “attribute” functionality.')
                ->defaultFalse()
            ->end()
        ;
    }
}
