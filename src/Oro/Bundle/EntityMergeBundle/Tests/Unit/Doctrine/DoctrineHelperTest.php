<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\Mapping\AdditionalMetadataProvider;
use Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DoctrineHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ClassMetadataFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $metadataFactory;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var Expr|\PHPUnit\Framework\MockObject\MockObject */
    private $expression;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $this->metadata = $this->createMock(ClassMetadata::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(AbstractQuery::class);
        $this->expression = $this->createMock(Expr::class);

        $additionalMetadataProvider = $this->createMock(AdditionalMetadataProvider::class);

        $this->doctrineHelper = new DoctrineHelper(
            $this->entityManager,
            $additionalMetadataProvider
        );
    }

    public function testGetEntityRepository()
    {
        $entityName = 'TestEntity';

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($entityName)
            ->willReturn($this->repository);

        $this->assertEquals($this->repository, $this->doctrineHelper->getEntityRepository($entityName));
    }

    public function testGetEntitiesByIds()
    {
        $entityIds = [1, 2, 3];
        $entities = [new EntityStub()];
        $identifier = 'id';

        $className = 'TestEntity';

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with($className)
            ->willReturn($this->repository);

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn($identifier);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('entity')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('expr')
            ->willReturn($this->expression);

        $inExpression = $this->createMock(Func::class);

        $this->expression->expects($this->once())
            ->method('in')
            ->with('entity.' . $identifier, ':entityIds')
            ->willReturn($inExpression);

        $this->queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('entityIds', $entityIds);

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with($inExpression);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('execute')
            ->willReturn($entities);

        $this->assertEquals($entities, $this->doctrineHelper->getEntitiesByIds($className, $entityIds));
    }

    public function testGetEntitiesByIdsForEmptyArray()
    {
        $this->assertEquals([], $this->doctrineHelper->getEntitiesByIds('TestEntity', []));
    }

    public function testGetSingleIdentifierFieldName()
    {
        $entityClass = 'stdClass';
        $identifier = 'id';

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn($identifier);

        $this->assertEquals($identifier, $this->doctrineHelper->getSingleIdentifierFieldName($entityClass));
    }

    public function testGetEntityIdentifierValue()
    {
        $entity = new EntityStub(1);

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(EntityStub::class)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn(['id' => $entity->getId()]);

        $this->assertSame($entity->getId(), $this->doctrineHelper->getEntityIdentifierValue($entity));
    }

    public function testGetEntityIdentifierValueFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiple id is not supported.');

        $entity = new EntityStub();

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(EntityStub::class)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($entity)
            ->willReturn(['id1' => 1, 'id2' => 2]);

        $this->doctrineHelper->getEntityIdentifierValue($entity);
    }

    public function testGetEntityIds()
    {
        $fooEntity = new EntityStub(1);
        $barEntity = new EntityStub(2);

        $this->entityManager->expects($this->exactly(2))
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects($this->exactly(2))
            ->method('getMetadataFor')
            ->with(EntityStub::class)
            ->willReturn($this->metadata);

        $this->metadata->expects($this->exactly(2))
            ->method('getIdentifierValues')
            ->willReturnCallback(function (EntityStub $entity) {
                return ['id' => $entity->getId()];
            });

        $this->assertEquals(
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
    ) {
        $this->entityManager->expects($this->exactly(2))
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects($this->exactly(2))
            ->method('getMetadataFor')
            ->willReturnMap([
                [get_class($firstObject), $this->metadata],
                [get_class($secondObject), $this->metadata]
            ]);

        $this->metadata->expects($this->exactly(2))
            ->method('getIdentifierValues')
            ->withConsecutive(
                [$this->identicalTo($firstObject)],
                [$this->identicalTo($secondObject)]
            )
            ->willReturnOnConsecutiveCalls(
                ['id' => $firstId],
                ['id' => $secondId]
            );

        $this->assertEquals($expected, $this->doctrineHelper->isEntityEqual($firstObject, $secondObject));
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

    public function testIsEntityEqualForNotSameClass()
    {
        $this->assertFalse($this->doctrineHelper->isEntityEqual(new EntityStub(), new \stdClass()));
    }

    public function testIsEntityEqualFailsForFirstNotObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$entity argument must be an object, "string" given.');

        $this->doctrineHelper->isEntityEqual('scalar', new \stdClass());
    }

    public function testIsEntityEqualFailsForSecondNotObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$other argument must be an object, "string" given.');

        $this->doctrineHelper->isEntityEqual(new \stdClass(), 'scalar');
    }

    public function testGetAllMetadata()
    {
        $expectedResult = [$this->metadata];

        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($this->metadataFactory);

        $this->metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->doctrineHelper->getAllMetadata());
    }
}
