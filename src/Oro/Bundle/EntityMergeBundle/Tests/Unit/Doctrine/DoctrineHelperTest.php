<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\Mapping\AdditionalMetadataProvider;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DoctrineHelperTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private AdditionalMetadataProvider&MockObject $additionalMetadataProvider;
    private EntityManagerInterface&MockObject $entityManager;
    private ClassMetadataFactory&MockObject $metadataFactory;
    private ClassMetadata&MockObject $metadata;
    private EntityRepository&MockObject $repository;
    private QueryBuilder&MockObject $queryBuilder;
    private AbstractQuery&MockObject $query;
    private DoctrineHelper $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->additionalMetadataProvider = $this->createMock(AdditionalMetadataProvider::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);

        $this->doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $this->doctrineHelper = new DoctrineHelper(
            $this->doctrine,
            $this->additionalMetadataProvider
        );
    }

    public function testGetEntityRepository(): void
    {
        $entityName = 'TestEntity';

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($this->repository);

        self::assertEquals($this->repository, $this->doctrineHelper->getEntityRepository($entityName));
    }

    public function testGetEntitiesByIds(): void
    {
        $entityIds = [1, 2, 3];
        $entities = [new EntityStub()];
        $identifier = 'id';

        $className = 'TestEntity';

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($this->repository);

        $this->entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($this->metadata);

        $this->metadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn($identifier);

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('entity')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(self::once())
            ->method('where')
            ->with('entity.' . $identifier . ' IN (:entityIds)')
            ->willReturnSelf();
        $this->queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('entityIds', $entityIds)
            ->willReturnSelf();
        $this->queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects(self::once())
            ->method('execute')
            ->willReturn($entities);

        self::assertEquals($entities, $this->doctrineHelper->getEntitiesByIds($className, $entityIds));
    }

    public function testGetEntitiesByIdsForEmptyArray(): void
    {
        self::assertEquals([], $this->doctrineHelper->getEntitiesByIds('TestEntity', []));
    }

    public function testGetSingleIdentifierFieldName(): void
    {
        $entityClass = 'stdClass';
        $identifier = 'id';

        $this->entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($this->metadata);

        $this->metadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn($identifier);

        self::assertEquals($identifier, $this->doctrineHelper->getSingleIdentifierFieldName($entityClass));
    }

    public function testGetEntityIdentifierValue(): void
    {
        $entity = new EntityStub(1);

        $this->entityManager->expects(self::once())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with(EntityStub::class)
            ->willReturn($this->metadata);

        $this->metadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn(['id' => $entity->getId()]);

        self::assertSame($entity->getId(), $this->doctrineHelper->getEntityIdentifierValue($entity));
    }

    public function testGetEntityIdentifierValueFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'An entity with composite ID is not supported. Entity: %s.',
            EntityStub::class
        ));

        $entity = new EntityStub();

        $this->entityManager->expects(self::once())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects(self::once())
            ->method('getMetadataFor')
            ->with(EntityStub::class)
            ->willReturn($this->metadata);

        $this->metadata->expects(self::once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn(['id1' => 1, 'id2' => 2]);

        $this->doctrineHelper->getEntityIdentifierValue($entity);
    }

    public function testGetEntityIds(): void
    {
        $fooEntity = new EntityStub(1);
        $barEntity = new EntityStub(2);

        $this->entityManager->expects(self::exactly(2))
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects(self::exactly(2))
            ->method('getMetadataFor')
            ->with(EntityStub::class)
            ->willReturn($this->metadata);

        $this->metadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->willReturnCallback(function (EntityStub $entity) {
                return ['id' => $entity->getId()];
            });

        self::assertEquals(
            [$fooEntity->getId(), $barEntity->getId()],
            $this->doctrineHelper->getEntityIds([$fooEntity, $barEntity])
        );
    }

    /**
     * @dataProvider isEntityEqualDataProvider
     */
    public function testIsEntityEqualForSameClass(
        object $firstObject,
        int $firstId,
        object $secondObject,
        int $secondId,
        bool $expected
    ): void {
        $this->entityManager->expects(self::exactly(2))
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects(self::exactly(2))
            ->method('getMetadataFor')
            ->willReturnMap([
                [get_class($firstObject), $this->metadata],
                [get_class($secondObject), $this->metadata]
            ]);

        $this->metadata->expects(self::exactly(2))
            ->method('getIdentifierValues')
            ->withConsecutive(
                [$this->identicalTo($firstObject)],
                [$this->identicalTo($secondObject)]
            )
            ->willReturnOnConsecutiveCalls(
                ['id' => $firstId],
                ['id' => $secondId]
            );

        self::assertEquals($expected, $this->doctrineHelper->isEntityEqual($firstObject, $secondObject));
    }

    public function isEntityEqualDataProvider(): array
    {
        return [
            'equal_class_equal_id' => [
                'firstObject' => new EntityStub(1),
                'firstId' => 1,
                'secondObject' => new EntityStub(2),
                'secondId' => 1,
                'expected' => true
            ],
            'equal_class_not_equal_id' => [
                'firstObject' => new EntityStub(1),
                'firstId' => 1,
                'secondObject' => new EntityStub(2),
                'secondId' => 2,
                'expected' => false
            ],
        ];
    }

    public function testGetInversedUnidirectionalAssociationMappings(): void
    {
        $className = 'Test\Entity';
        $result = ['key' => 'value'];

        $this->additionalMetadataProvider->expects(self::once())
            ->method('getInversedUnidirectionalAssociationMappings')
            ->with($className)
            ->willReturn($result);

        self::assertSame(
            $result,
            $this->doctrineHelper->getInversedUnidirectionalAssociationMappings($className)
        );
    }

    public function testIsEntityEqualForNotSameClass(): void
    {
        self::assertFalse($this->doctrineHelper->isEntityEqual(new EntityStub(), new \stdClass()));
    }

    public function testGetAllMetadata(): void
    {
        $expectedResult = [$this->metadata];

        $this->entityManager->expects(self::once())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects(self::once())
            ->method('getAllMetadata')
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->doctrineHelper->getAllMetadata());
    }
}
