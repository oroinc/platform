<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\EntityConfig;

use Oro\Bundle\OrganizationBundle\EntityConfig\OwnershipEntityConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class OwnershipEntityConfigurationTest extends TestCase
{
    private OwnershipEntityConfiguration $configuration;

    #[\Override]
    protected function setUp(): void
    {
        $this->configuration = new OwnershipEntityConfiguration();
    }

    private function processConfiguration(array $config): array
    {
        $treeBuilder = new TreeBuilder('ownership');
        $this->configuration->configure($treeBuilder->getRootNode()->children());

        $processor = new Processor();

        return $processor->process($treeBuilder->buildTree(), [$config]);
    }

    public function testGetSectionName(): void
    {
        self::assertEquals('ownership', $this->configuration->getSectionName());
    }

    /**
     * @dataProvider validConfigurationDataProvider
     */
    public function testValidConfiguration(array $config): void
    {
        $this->processConfiguration($config);
    }

    public static function validConfigurationDataProvider(): array
    {
        return [
            'empty config' => [[]],
            'ORGANIZATION with required fields' => [
                [
                    'owner_type' => 'ORGANIZATION',
                    'owner_field_name' => 'organization',
                    'owner_column_name' => 'organization_id'
                ]
            ],
            'USER with all required fields' => [
                [
                    'owner_type' => 'USER',
                    'owner_field_name' => 'owner',
                    'owner_column_name' => 'owner_id',
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ]
            ],
            'BUSINESS_UNIT with all required fields' => [
                [
                    'owner_type' => 'BUSINESS_UNIT',
                    'owner_field_name' => 'owner',
                    'owner_column_name' => 'owner_id',
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ]
            ],
            'frontend fields' => [
                [
                    'frontend_owner_type' => 'FRONTEND_USER',
                    'frontend_owner_field_name' => 'customerUser',
                    'frontend_owner_column_name' => 'customer_user_id',
                    'frontend_customer_field_name' => 'customer',
                    'frontend_customer_column_name' => 'customer_id'
                ]
            ]
        ];
    }

    /**
     * @dataProvider invalidConfigDataProvider
     */
    public function testInvalidConfig(array $config, string $expectedMessage): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->processConfiguration($config);
    }

    public static function invalidConfigDataProvider(): array
    {
        return [
            'USER without any fields' => [
                ['owner_type' => 'USER'],
                'owner_type "USER" requires fields: owner_field_name, owner_column_name, '
                . 'organization_field_name, organization_column_name. Missing: owner_field_name, '
                . 'owner_column_name, organization_field_name, organization_column_name.'
            ],
            'USER missing organization_field_name' => [
                [
                    'owner_type' => 'USER',
                    'owner_field_name' => 'owner',
                    'owner_column_name' => 'owner_id',
                    'organization_column_name' => 'organization_id'
                ],
                'owner_type "USER" requires fields: owner_field_name, owner_column_name, '
                . 'organization_field_name, organization_column_name. Missing: organization_field_name.'
            ],
            'BUSINESS_UNIT without any fields' => [
                ['owner_type' => 'BUSINESS_UNIT'],
                'owner_type "BUSINESS_UNIT" requires fields: owner_field_name, owner_column_name, '
                . 'organization_field_name, organization_column_name. Missing: owner_field_name, '
                . 'owner_column_name, organization_field_name, organization_column_name.'
            ],
            'BUSINESS_UNIT missing owner_column_name' => [
                [
                    'owner_type' => 'BUSINESS_UNIT',
                    'owner_field_name' => 'owner',
                    'organization_field_name' => 'organization',
                    'organization_column_name' => 'organization_id'
                ],
                'owner_type "BUSINESS_UNIT" requires fields: owner_field_name, owner_column_name, '
                . 'organization_field_name, organization_column_name. Missing: owner_column_name.'
            ],
            'ORGANIZATION without any fields' => [
                ['owner_type' => 'ORGANIZATION'],
                'owner_type "ORGANIZATION" requires fields: owner_field_name, owner_column_name. '
                . 'Missing: owner_field_name, owner_column_name.'
            ],
            'ORGANIZATION missing owner_column_name' => [
                [
                    'owner_type' => 'ORGANIZATION',
                    'owner_field_name' => 'organization'
                ],
                'owner_type "ORGANIZATION" requires fields: owner_field_name, owner_column_name. '
                . 'Missing: owner_column_name.'
            ],
            'ORGANIZATION missing owner_field_name' => [
                [
                    'owner_type' => 'ORGANIZATION',
                    'owner_column_name' => 'organization_id'
                ],
                'owner_type "ORGANIZATION" requires fields: owner_field_name, owner_column_name. '
                . 'Missing: owner_field_name.'
            ]
        ];
    }
}
