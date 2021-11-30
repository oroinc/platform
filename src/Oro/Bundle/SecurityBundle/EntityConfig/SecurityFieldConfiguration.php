<?php

namespace Oro\Bundle\SecurityBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\FieldConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations field config for security scope.
 */
class SecurityFieldConfiguration implements FieldConfigInterface
{
    public function getSectionName(): string
    {
        return 'security';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('permissions')
                ->info('`string` the following permissions are supported for fields: VIEW, EDIT.')
            ->end()
        ;
    }
}
