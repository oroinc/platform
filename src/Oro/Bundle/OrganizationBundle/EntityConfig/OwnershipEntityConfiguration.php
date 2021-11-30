<?php

namespace Oro\Bundle\OrganizationBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Provides validations entity config for ownership scope.
 */
class OwnershipEntityConfiguration implements EntityConfigInterface
{
    public function getSectionName(): string
    {
        return 'ownership';
    }

    public function configure(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->scalarNode('owner_type')
                ->info('`string` can have the following status:' . "\n" .
                ' - ORGANIZATION needs to set owner_field_name and owner_column_name' . "\n" .
                ' - BUSINESS_UNIT needs to set owner_field_name, owner_column_name, organization_field_name ' .
                'and organization_column_name' . "\n" .
                ' - USER needs to set owner_field_name, owner_column_name, organization_field_name and ' .
                'organization_column_name')
            ->end()
            ->scalarNode('owner_field_name')
                ->info('`string` the name of owner field if owner_type is ORGANIZATION than this parameter ' .
                'is equal with organization_field_name')
            ->end()
            ->scalarNode('owner_column_name')
                ->info('`string` the name of owner column if owner_type is ORGANIZATION than this parameter ' .
                'is equal with organization_column_name')
            ->end()
            ->scalarNode('organization_field_name')
                ->info('`string` the name of organization field if owner_type is ORGANIZATION than this parameter ' .
                'is equal with owner_field_name')
            ->end()
            ->scalarNode('organization_column_name')
                ->info('`string` the name of organization column if owner_type is ORGANIZATION than this parameter ' .
                'is equal with owner_column_name')
            ->end()
            ->scalarNode('frontend_owner_type')
                ->info('`string` can have the following status:' . "\n" .
                ' - FRONTEND_USER' . "\n" .
                ' - FRONTEND_CUSTOMER')
            ->end()
            ->scalarNode('frontend_owner_field_name')
                ->info('`string` the same as owner_field_name but for front part')
            ->end()
            ->scalarNode('frontend_owner_column_name')
                ->info('`string` the same as owner_column_name but for front part')
            ->end()
            ->scalarNode('frontend_customer_field_name')
                ->info('`string` the name of customer field for front part')
            ->end()
            ->scalarNode('frontend_customer_column_name')
                ->info('`string` the name of customer column for front part')
            ->end()
        ;
    }
}
