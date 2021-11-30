<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for fallback scope.
 */
class FallbackFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'fallback';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('fallbackList')
                ->info('`array` contains a list of possible fallback entities.')
                ->prototype('variable')->end()
            ->end()
            ->scalarNode('fallbackType')
                ->info('`string` specifies the type of the field value.')
            ->end()
        ;
    }
}
