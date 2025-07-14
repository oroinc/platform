<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Field;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestOrganization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DatabaseHelperTest extends TestCase
{
    private const TEST_CLASS = 'stdClass';

    private DoctrineHelper&MockObject $doctrineHelper;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private FieldHelper&MockObject $fieldHelper;
    private OwnershipMetadataProviderInterface&MockObject $ownershipMetadataProvider;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityRepository&MockObject $repository;
    private ClassMetadata&MockObject $metadata;
    private DatabaseHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->ownershipMetadataProvider = $this->createMock(OwnershipMetadataProviderInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->metadata = $this->createMock(ClassMetadata::class);

        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_CLASS)
            ->willReturn($this->metadata);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(self::TEST_CLASS)
            ->willReturn($this->repository);

        $container = TestContainerBuilder::create()
            ->add(FieldHelper::class, $this->fieldHelper)
            ->getContainer($this);

        $this->helper = new DatabaseHelper(
            $this->doctrineHelper,
            $this->tokenAccessor,
            $this->ownershipMetadataProvider,
            $container
        );
    }

    public function testFind(): void
    {
        $entity = new \stdClass();
        $identifier = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(self::TEST_CLASS, $identifier)
            ->willReturn($entity);

        $found = $this->helper->find(self::TEST_CLASS, $identifier);
        $this->assertEquals($entity, $found);

        $this->assertSame($found, $this->helper->find(self::TEST_CLASS, $identifier));
    }

    public function testFindObjectFromAnotherOrganization(): void
    {
        $entityOrganization = new TestOrganization();
        $entityOrganization->setId(2);
        $entity = new TestEntity();
        $entity->setOrganization($entityOrganization);
        $identifier = 1;
        $entity->setId($identifier);

        $currentOrganization = new TestOrganization();
        $currentOrganization->setId(1);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($currentOrganization);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn($currentOrganization->getId());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(self::TEST_CLASS, $identifier)
            ->willReturn($entity);

        $metadata = new OwnershipMetadata(
            'USER',
            'owner',
            'owner',
            'organization',
            'organization'
        );

        $this->fieldHelper->expects($this->once())
            ->method('getObjectValue')
            ->willReturn($entity->getOrganization());

        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);

        $this->assertNull($this->helper->find(self::TEST_CLASS, $identifier));
    }

    public function testFindObjectFromAnotherOrganizationWithoutLimitations(): void
    {
        $currentOrganization = new TestOrganization();
        $currentOrganization->setId(1);

        $entityOrganization = new TestOrganization();
        $entityOrganization->setId(2);

        $identifier = 42;

        $entity = new TestEntity();
        $entity->setId($identifier);
        $entity->setOrganization($entityOrganization);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(self::TEST_CLASS, $identifier)
            ->willReturn($entity);

        $this->tokenAccessor->expects($this->never())
            ->method($this->anything());

        $this->fieldHelper->expects($this->never())
            ->method('getObjectValue');

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->assertSame($entity, $this->helper->find(self::TEST_CLASS, $identifier, false));
    }

    public function testGetIdentifier(): void
    {
        $entity = new \stdClass();
        $identifier = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($identifier);

        $this->assertEquals($identifier, $this->helper->getIdentifier($entity));
    }

    public function testGetIdentifierFieldName(): void
    {
        $fieldName = 'id';

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::TEST_CLASS)
            ->willReturn($fieldName);

        $this->assertEquals($fieldName, $this->helper->getIdentifierFieldName(self::TEST_CLASS));
    }

    /**
     * @dataProvider isCascadePersistDataProvider
     */
    public function testIsCascadePersist(array $mapping, bool $isCascade): void
    {
        $fieldName = 'relation';

        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->willReturn($mapping);

        $this->assertEquals($isCascade, $this->helper->isCascadePersist(self::TEST_CLASS, $fieldName));
    }

    public function isCascadePersistDataProvider(): array
    {
        return [
            'no cascade operations' => [
                'mapping'   => [],
                'isCascade' => false,
            ],
            'no cascade persist' => [
                'mapping'   => ['cascade' => ['remove']],
                'isCascade' => false,
            ],
            'cascade persist' => [
                'mapping'   => ['cascade' => ['persist']],
                'isCascade' => true,
            ],
        ];
    }

    public function testResetIdentifier(): void
    {
        $entity = new \stdClass();
        $fieldName = 'id';

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::TEST_CLASS)
            ->willReturn($fieldName);

        $this->metadata->expects($this->once())
            ->method('setIdentifierValues')
            ->with($entity, [$fieldName => null])
            ->willReturn($fieldName);

        $this->helper->resetIdentifier($entity);
    }

    public function testRefreshEntity(): void
    {
        $entity = new \stdClass();

        $this->entityManager->expects($this->once())
            ->method('refresh')
            ->with($entity);

        $this->helper->refreshEntity($entity);
    }

    public function testGetOwnerFieldName(): void
    {
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(\stdClass::class)
            ->willReturn(
                new OwnershipMetadata(
                    'USER',
                    'owner',
                    'owner_id',
                    'organization',
                    'organization_id'
                )
            );

        $this->assertEquals('owner', $this->helper->getOwnerFieldName(\stdClass::class));
    }

    /**
     * @dataProvider getInversedRelationFieldNameDataProvider
     */
    public function testGetInversedRelationFieldName(array $association, ?string $expectedField): void
    {
        $fieldName = 'relation';

        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->willReturn($association);

        $this->assertSame($expectedField, $this->helper->getInversedRelationFieldName(self::TEST_CLASS, $fieldName));
    }

    public function getInversedRelationFieldNameDataProvider(): array
    {
        return [
            'mapped by field' => [
                'association' => ['mappedBy' => 'field'],
                'expectedField' => 'field',
            ],
            'inversed by field' => [
                'association' => ['inversedBy' => 'field'],
                'expectedField' => 'field',
            ],
            'no inversed field' => [
                'association' => [],
                'expectedField' => null,
            ],
        ];
    }

    /**
     * @dataProvider isSingleInversedRelationDataProvider
     */
    public function testIsSingleInversedRelation(int $type, bool $expected): void
    {
        $fieldName = 'relation';

        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->willReturn(['type' => $type]);

        $this->assertEquals($expected, $this->helper->isSingleInversedRelation(self::TEST_CLASS, $fieldName));
    }

    public function isSingleInversedRelationDataProvider(): array
    {
        return [
            'one to one'   => [ClassMetadata::ONE_TO_ONE, true],
            'one to many'  => [ClassMetadata::ONE_TO_MANY, true],
            'many to one'  => [ClassMetadata::MANY_TO_ONE, false],
            'many to many' => [ClassMetadata::MANY_TO_MANY, false],
        ];
    }

    public function testGetEntityReference(): void
    {
        $entity = new \stdClass();
        $reference = new \stdClass();
        $entityName = get_class($entity);
        $identifier = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn($identifier);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($entityName, $identifier)
            ->willReturn($reference);

        $this->assertEquals($reference, $this->helper->getEntityReference($entity));
    }

    public function testFindOneByCacheWithoutEntity(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setParameters')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->assertNull($this->helper->findOneBy(self::TEST_CLASS, ['field1' => 'value1', 'field2' => 'value2']));

        // check cache
        $this->assertNull($this->helper->findOneBy(self::TEST_CLASS, ['field2' => 'value2', 'field1' => 'value1']));
    }
}
