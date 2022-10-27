<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for testing.
 */
class SecondSimpleEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'simple';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('other_simple_string')->end()
        ;
    }
}
