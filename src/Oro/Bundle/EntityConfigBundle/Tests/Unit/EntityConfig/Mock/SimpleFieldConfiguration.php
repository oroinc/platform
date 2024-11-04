<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for testing.
 */
class SimpleFieldConfiguration implements FieldConfigInterface
{
    #[\Override]
    public function getSectionName(): string
    {
        return 'simple';
    }

    #[\Override]
    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('simple_string')->end()
        ;
    }
}
