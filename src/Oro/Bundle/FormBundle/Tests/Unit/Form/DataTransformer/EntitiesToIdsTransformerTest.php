<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Oro\Bundle\FormBundle\Tests\Unit\Fixtures\Entity\TestEntity;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntitiesToIdsTransformerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $em;
    private ClassMetadata&MockObject $classMetadata;
    private EntityRepository&MockObject $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($this->classMetadata);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(TestEntity::class)
            ->willReturn($this->repository);
    }

    private function getTransformer(?string $property, mixed $queryBuilderCallback): EntitiesToIdsTransformer
    {
        return new EntitiesToIdsTransformer($this->doctrine, TestEntity::class, $property, $queryBuilderCallback);
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
    public function testTransform(string $property, array $value, array $expectedValue): void
    {
        $transformer = $this->getTransformer($property, null);
        self::assertEquals($expectedValue, $transformer->transform($value));
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

    public function testTransformFailsWhenValueInNotAnArray(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $transformer = $this->getTransformer('id', null);
        $transformer->transform('invalid value');
    }

    public function testReverseTransformForEmptyArray(): void
    {
        $transformer = $this->getTransformer('id', null);
        self::assertSame([], $transformer->reverseTransform([]));
    }

    public function testReverseTransformDefault(): void
    {
        $value = [1, 2, 3, 4];
        $expectedValue = $this->createEntityList('id', [1, 2, 3, 4]);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->classMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.id IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('ids', $value)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('execute')
            ->willReturn($expectedValue);

        $transformer = $this->getTransformer(null, null);
        self::assertEquals($expectedValue, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWithCustomProperty(): void
    {
        $value = ['a', 'b', 'c'];
        $expectedValue = $this->createEntityList('name', ['a', 'b', 'c']);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->classMetadata->expects(self::never())
            ->method('getSingleIdentifierFieldName');

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.name IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('ids', $value)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('execute')
            ->willReturn($expectedValue);

        $transformer = $this->getTransformer('name', null);
        self::assertEquals($expectedValue, $transformer->reverseTransform($value));
    }

    public function testReverseTransformWithCustomQueryBuilderCallback(): void
    {
        $value = [1, 2, 3, 4];
        $expectedValue = $this->createEntityList('id', [1, 2, 3, 4]);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('o')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('o.id IN (:values)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('values', $value)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('execute')
            ->willReturn($expectedValue);

        $transformer = $this->getTransformer(null, function ($repository, array $ids) {
            $result = $repository->createQueryBuilder('o');
            $result
                ->where('o.id IN (:values)')
                ->setParameter('values', $ids);

            return $result;
        });
        self::assertEquals($expectedValue, $transformer->reverseTransform($value));
    }

    public function testReverseTransformForNotArray(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "array", "string" given');

        $transformer = $this->getTransformer('id', null);
        $transformer->reverseTransform('1,2,3,4,5');
    }

    public function testReverseTransformWhenEntitiesCountMismatchIdsCount(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Could not find all entities for the given IDs');

        $value = [1, 2, 3, 4, 5];
        $loadedEntities = $this->createEntityList('id', [1, 2, 3, 4]);

        $query = $this->createMock(AbstractQuery::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->classMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.id IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('ids', $value)
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('execute')
            ->willReturn($loadedEntities);

        $transformer = $this->getTransformer(null, null);
        $transformer->reverseTransform($value);
    }

    public function testReverseTransformWithInvalidQueryBuilderCallback(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Doctrine\ORM\QueryBuilder", "stdClass" given');

        $transformer = $this->getTransformer(null, function () {
            return new \stdClass();
        });
        $transformer->reverseTransform([1, 2, 3, 4]);
    }

    public function testCreateFailsWhenCannotGetIdProperty(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage(sprintf(
            'Cannot get id property path of entity. "%s" has composite primary key.',
            TestEntity::class
        ));

        $this->classMetadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willThrowException(new MappingException());

        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($this->classMetadata);

        $transformer = $this->getTransformer(null, null);
        $transformer->transform($this->createEntityList('id', [1, 2, 3, 4]));
    }

    public function testCreateFailsWhenQueryBuilderCallbackIsNotCallable(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", "array" given');

        $this->getTransformer('id', []);
    }
}
