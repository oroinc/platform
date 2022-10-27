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
            ->node('immutable', 'normalized_boolean')
                ->info('`boolean` is used to prohibit changing the attribute association state (regardless ' .
                    'of whether it is enabled or not) for the entity. '.
                    'If TRUE, than the current state cannot be changed.')
                ->defaultFalse()
            ->end()
        ;
    }
}
