<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation\Mock;

use Oro\Bundle\EntityConfigBundle\Config\Validation\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for testing.
 */
class SimpleFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'simple';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('simple_string')->end()
        ;
    }
}
