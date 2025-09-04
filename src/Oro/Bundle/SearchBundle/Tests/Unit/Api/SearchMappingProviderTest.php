<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SearchBundle\Api\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchMappingProviderTest extends TestCase
{
    private AbstractSearchMappingProvider&MockObject $searchMappingProvider;
    private DoctrineHelper&MockObject $doctrineHelper;
    private OwnershipMetadataProviderInterface&MockObject $ownershipMetadataProvider;
    private SearchMappingProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->searchMappingProvider = $this->createMock(AbstractSearchMappingProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);

        $this->provider = new SearchMappingProvider(
            $this->searchMappingProvider,
            $this->doctrineHelper,
            $this->ownershipMetadataProvider
        );
    }

    public function testIsSearchableEntityWhenEntityIsSearchable(): void
    {
        $entityClass = 'Test\Entity';
        $this->searchMappingProvider->expects(self::once())
            ->method('isClassSupported')
            ->with($entityClass)
            ->willReturn(true);

        self::assertTrue($this->provider->isSearchableEntity($entityClass));
    }

    public function testIsSearchableEntityWhenEntityIsNotSearchable(): void
    {
        $entityClass = 'Test\Entity';
        $this->searchMappingProvider->expects(self::once())
            ->method('isClassSupported')
            ->with($entityClass)
            ->willReturn(false);

        self::assertFalse($this->provider->isSearchableEntity($entityClass));
    }

    public function testGetSearchFieldsForEntityWithoutOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $ownershipMetadata = new OwnershipMetadata('NONE');

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                ['name' => 'entityField3', 'type' => 'integer', 'entityFields' => ['field_3']],
                ['name' => 'field1', 'type' => 'integer', 'entityFields' => ['field1']],
                ['name' => 'field2', 'type' => 'datetime', 'entityFields' => ['field2.field21']],
                ['name' => 'field4', 'type' => 'text', 'entityFields' => ['field2.field24']],
                ['name' => 'id', 'type' => 'integer', 'entityFields' => ['id']],
                ['name' => 'allText', 'type' => 'text', 'entityFields' => ['field2.field24']]
            ],
            $this->provider->getSearchFields($entityClass)
        );
    }

    public function testGetSearchFieldsForEntityWithUserOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']],
                ['name' => 'owner', 'target_type' => 'integer', 'target_fields' => ['entity_owner']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('owner')
            ->willReturn(User::class);

        $ownershipMetadata = new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'bu_owner_id');

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                ['name' => 'entityField3', 'type' => 'integer', 'entityFields' => ['field_3']],
                ['name' => 'field1', 'type' => 'integer', 'entityFields' => ['field1']],
                ['name' => 'field2', 'type' => 'datetime', 'entityFields' => ['field2.field21']],
                ['name' => 'field4', 'type' => 'text', 'entityFields' => ['field2.field24']],
                ['name' => 'id', 'type' => 'integer', 'entityFields' => ['id']],
                ['name' => 'user', 'type' => 'integer', 'entityFields' => ['owner']],
                ['name' => 'allText', 'type' => 'text', 'entityFields' => ['field2.field24']]
            ],
            $this->provider->getSearchFields($entityClass)
        );
    }

    public function testGetSearchFieldsForEntityWithBusinessUnitOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']],
                ['name' => 'owner', 'target_type' => 'integer', 'target_fields' => ['entity_owner']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('owner')
            ->willReturn(BusinessUnit::class);

        $ownershipMetadata = new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'bu_owner_id');

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                ['name' => 'businessUnit', 'type' => 'integer', 'entityFields' => ['owner']],
                ['name' => 'entityField3', 'type' => 'integer', 'entityFields' => ['field_3']],
                ['name' => 'field1', 'type' => 'integer', 'entityFields' => ['field1']],
                ['name' => 'field2', 'type' => 'datetime', 'entityFields' => ['field2.field21']],
                ['name' => 'field4', 'type' => 'text', 'entityFields' => ['field2.field24']],
                ['name' => 'id', 'type' => 'integer', 'entityFields' => ['id']],
                ['name' => 'allText', 'type' => 'text', 'entityFields' => ['field2.field24']]
            ],
            $this->provider->getSearchFields($entityClass)
        );
    }

    public function testGetSearchFieldTypesForEntityWithoutOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $ownershipMetadata = new OwnershipMetadata('NONE');

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                'id' => 'integer',
                'field1' => 'integer',
                'field2' => 'datetime',
                'entityField3' => 'integer',
                'field4' => 'text',
                'allText' => 'text'
            ],
            $this->provider->getSearchFieldTypes($entityClass)
        );
    }

    public function testGetSearchFieldTypesForEntityWithUserOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']],
                ['name' => 'owner', 'target_type' => 'integer', 'target_fields' => ['entity_owner']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('owner')
            ->willReturn(User::class);

        $ownershipMetadata = new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'bu_owner_id');

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                'id' => 'integer',
                'field1' => 'integer',
                'field2' => 'datetime',
                'entityField3' => 'integer',
                'field4' => 'text',
                'user' => 'integer',
                'allText' => 'text'
            ],
            $this->provider->getSearchFieldTypes($entityClass)
        );
    }

    public function testGetSearchFieldTypesForEntityWithBusinessUnitOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']],
                ['name' => 'owner', 'target_type' => 'integer', 'target_fields' => ['entity_owner']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('owner')
            ->willReturn(BusinessUnit::class);

        $ownershipMetadata = new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'bu_owner_id');

        $this->searchMappingProvider->expects(self::exactly(2))
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                'id' => 'integer',
                'field1' => 'integer',
                'field2' => 'datetime',
                'entityField3' => 'integer',
                'field4' => 'text',
                'businessUnit' => 'integer',
                'allText' => 'text'
            ],
            $this->provider->getSearchFieldTypes($entityClass)
        );
    }

    public function testGetFieldMappingsForEntityWithoutOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $ownershipMetadata = new OwnershipMetadata('NONE');

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                'id' => 'entity_id',
                'field1' => 'field1',
                'field2' => 'field2',
                'entityField3' => 'entity_field_3',
                'field4' => 'field_4',
                'allText' => 'all_text'
            ],
            $this->provider->getFieldMappings($entityClass)
        );
    }

    public function testGetFieldMappingsForEntityWithUserOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']],
                ['name' => 'owner', 'target_type' => 'integer', 'target_fields' => ['entity_owner']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('owner')
            ->willReturn(User::class);

        $ownershipMetadata = new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'bu_owner_id');

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                'id' => 'entity_id',
                'field1' => 'field1',
                'field2' => 'field2',
                'entityField3' => 'entity_field_3',
                'field4' => 'field_4',
                'user' => 'entity_owner',
                'allText' => 'all_text'
            ],
            $this->provider->getFieldMappings($entityClass)
        );
    }

    public function testGetFieldMappingsForEntityWithBusinessUnitOwner(): void
    {
        $entityClass = 'Test\Entity';
        $mapping = [
            'fields' => [
                ['name' => 'id', 'target_type' => 'integer', 'target_fields' => ['entity_id']],
                ['name' => 'field1', 'target_type' => 'integer', 'target_fields' => ['field1']],
                [
                    'name' => 'field2',
                    'target_fields' => [],
                    'relation_fields' => [
                        ['name' => 'field21', 'target_type' => 'datetime', 'target_fields' => ['field2']],
                        ['name' => 'field24', 'target_type' => 'text', 'target_fields' => ['field_4']],
                    ]
                ],
                ['name' => 'field_3', 'target_type' => 'integer', 'target_fields' => ['entity_field_3']],
                ['name' => 'owner', 'target_type' => 'integer', 'target_fields' => ['entity_owner']]
            ]
        ];

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $entityMetadata->expects(self::atLeastOnce())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $entityMetadata->expects(self::once())
            ->method('getAssociationTargetClass')
            ->with('owner')
            ->willReturn(BusinessUnit::class);

        $ownershipMetadata = new OwnershipMetadata('BUSINESS_UNIT', 'owner', 'bu_owner_id');

        $this->searchMappingProvider->expects(self::once())
            ->method('getEntityConfig')
            ->with($entityClass)
            ->willReturn($mapping);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityMetadataForClass')
            ->with($entityClass)
            ->willReturn($entityMetadata);
        $this->ownershipMetadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with($entityClass)
            ->willReturn($ownershipMetadata);

        self::assertEquals(
            [
                'id' => 'entity_id',
                'field1' => 'field1',
                'field2' => 'field2',
                'entityField3' => 'entity_field_3',
                'field4' => 'field_4',
                'businessUnit' => 'entity_owner',
                'allText' => 'all_text'
            ],
            $this->provider->getFieldMappings($entityClass)
        );
    }

    public function testGetAllTextFieldName(): void
    {
        self::assertEquals('allText', $this->provider->getAllTextFieldName());
    }
}
