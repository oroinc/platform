<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for testing.
 */
class SimpleEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'simple';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('simple_string')
            ->end()
            ->booleanNode('simple_bool')
            ->defaultFalse()
            ->end()
            ->arrayNode('simple_array')
                ->prototype('variable')->end()
            ->end()
        ;
    }
}
