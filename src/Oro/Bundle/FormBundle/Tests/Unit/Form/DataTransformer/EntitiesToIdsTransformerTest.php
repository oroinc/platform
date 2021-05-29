<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\TestEntity;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntitiesToIdsTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $classMetadata;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($this->classMetadata);
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with(TestEntity::class)
            ->willReturn($this->repository);
    }

    private function getTransformer($property, $queryBuilderCallback): EntitiesToIdsTransformer
    {
        return new EntitiesToIdsTransformer(
            $this->entityManager,
            TestEntity::class,
            $property,
            $queryBuilderCallback
        );
    }

    private function createEntityList(string $property, array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            $entity = new TestEntity();
            ReflectionUtil::setPropertyValue($entity, $property, $value);

            $result[] = $entity;
        }

        return $result;
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(string $property, array $value, array $expectedValue)
    {
        $transformer = $this->getTransformer($property, null);
        $this->assertEquals($expectedValue, $transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            'default'       => [
                'id',
                $this->createEntityList('id', [1, 2, 3, 4]),
                [1, 2, 3, 4]
            ],
            'code property' => [
                'name',
                $this->createEntityList('name', ['a', 'b', 'c']),
                ['a', 'b', 'c']
            ],
            'empty'         => [
                'id',
                [],
                []
            ],
        ];
    }

    public function testTransformFailsWhenValueInNotAnArray()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $transformer = $this->getTransformer('id', null);
        $transformer->transform('invalid value');
    }

    public function testReverseTransformForEmptyArray()
    {
        $transformer = $this->getTransformer('id', null);
        $this->assertSame([], $transformer->reverseTransform([]));
    }

    public function testReverseTransformDefault()
    {
        $value = [1, 2, 3, 4];
        $expectedValue = $this->createEntityList('id', [1, 2, 3, 4]);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.id IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('ids', $value)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->willReturn($expectedValue);

        $transformer = $this->getTransformer(null, null);
        $this->assertEquals($expectedValue, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWithCustomProperty()
    {
        $value = ['a', 'b', 'c'];
        $expectedValue = $this->createEntityList('name', ['a', 'b', 'c']);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->classMetadata->expects($this->never())
            ->method('getSingleIdentifierFieldName');

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.name IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('ids', $value)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->willReturn($expectedValue);

        $transformer = $this->getTransformer('name', null);
        $this->assertEquals($expectedValue, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWithCustomQueryBuilderCallback()
    {
        $value = [1, 2, 3, 4];
        $expectedValue = $this->createEntityList('id', [1, 2, 3, 4]);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('o.id IN (:values)')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('values', $value)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->willReturn($expectedValue);

        $transformer = $this->getTransformer(null, function ($repository, array $ids) {
            $result = $repository->createQueryBuilder('o');
            $result
                ->where('o.id IN (:values)')
                ->setParameter('values', $ids);

            return $result;
        });
        $this->assertEquals($expectedValue, $transformer->reverseTransform($value));
    }

    public function testReverseTransformForNotArray()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $transformer = $this->getTransformer('id', null);
        $transformer->reverseTransform('1,2,3,4,5');
    }

    public function testReverseTransformWhenEntitiesCountMismatchIdsCount()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Could not find all entities for the given IDs');

        $value = [1, 2, 3, 4, 5];
        $loadedEntities = $this->createEntityList('id', [1, 2, 3, 4]);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.id IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('ids', $value)
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('execute')
            ->willReturn($loadedEntities);

        $transformer = $this->getTransformer(null, null);
        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWithInvalidQueryBuilderCallback()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Doctrine\ORM\QueryBuilder", "stdClass" given');

        $this->classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $transformer = $this->getTransformer(null, function () {
            return new \stdClass();
        });
        $transformer->reverseTransform([1, 2, 3, 4]);
    }

    public function testCreateFailsWhenCannotGetIdProperty()
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot get id property path of entity. "%s" has composite primary key.',
            TestEntity::class
        ));

        $this->classMetadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willThrowException(new MappingException());

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($this->classMetadata);

        $this->getTransformer(null, null);
    }

    public function testCreateFailsWhenQueryBuilderCallbackIsNotCallable()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", "array" given');

        $this->getTransformer('id', []);
    }
}
