<?php

namespace Oro\Bundle\OrganizationBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for global organization scope.
 */
class GlobalOrganizationAwareConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'global_organization';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->booleanNode('is_global_aware')
                ->info('`boolean` is global organization aware flag.')
                ->defaultFalse()
            ->end();
    }
}
