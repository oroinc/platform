<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config\Validation\Mock;

use Oro\Bundle\EntityConfigBundle\Config\Validation\EntityConfigInterface;
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
            ->scalarNode('simple_string')->end()
            ->booleanNode('simple_bool')->end()
            ->arrayNode('simple_array')
                ->ignoreExtraKeys()
            ->end()
        ;
    }
}
