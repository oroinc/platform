<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Validation;

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
            ->booleanNode('has_attributes')
                ->info('`boolean` is used to enable the “attribute” functionality.')
            ->end()
        ;
    }
}
