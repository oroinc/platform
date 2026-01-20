<?php

namespace Oro\Bundle\OrganizationBundle\EntityConfig;

use Oro\Bundle\EntityConfigBundle\EntityConfig\EntityConfigInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Provides validations entity config for ownership scope.
 */
class OwnershipEntityConfiguration implements EntityConfigInterface
{
    private const array OWNER_TYPES_WITH_ORGANIZATION = ['USER', 'BUSINESS_UNIT'];
    private const string OWNER_TYPE_ORGANIZATION = 'ORGANIZATION';

    private const array FIELDS_FOR_USER_OR_BUSINESS_UNIT = [
        'owner_field_name',
        'owner_column_name',
        'organization_field_name',
        'organization_column_name',
    ];

    private const array FIELDS_FOR_ORGANIZATION = [
        'owner_field_name',
        'owner_column_name',
    ];

    #[\Override]
    public function getSectionName(): string
    {
        return 'ownership';
    }

    #[\Override]
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
        ->end()
        ->validate()
            ->always($this->validateOwnershipFields(...))
        ->end();
    }

    private function validateOwnershipFields(array $config): array
    {
        $ownerType = $config['owner_type'] ?? null;

        if (null === $ownerType) {
            return $config;
        }

        if (in_array($ownerType, self::OWNER_TYPES_WITH_ORGANIZATION, true)) {
            $this->assertRequiredFields($config, $ownerType, self::FIELDS_FOR_USER_OR_BUSINESS_UNIT);
        }
        if (self::OWNER_TYPE_ORGANIZATION === $ownerType) {
            $this->assertRequiredFields($config, $ownerType, self::FIELDS_FOR_ORGANIZATION);
        }

        return $config;
    }

    private function assertRequiredFields(array $config, string $ownerType, array $requiredFields): void
    {
        $missingFields = array_filter($requiredFields, static fn (string $field) => empty($config[$field]));

        if ($missingFields) {
            throw new InvalidConfigurationException(sprintf(
                'owner_type "%s" requires fields: %s. Missing: %s.',
                $ownerType,
                implode(', ', $requiredFields),
                implode(', ', $missingFields)
            ));
        }
    }
}
