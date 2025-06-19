<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityToIdTransformerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);
    }

    private function getTransformer(?string $property, mixed $queryBuilderCallback): EntityToIdTransformer
    {
        return new EntityToIdTransformer($this->doctrine, 'TestClass', $property, $queryBuilderCallback);
    }

    private function createEntity(int $id): \stdClass
    {
        $result = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $result->expects(self::any())
            ->method('getId')
            ->willReturn($id);

        return $result;
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(string $property, ?object $value, ?int $expectedValue): void
    {
        $transformer = $this->getTransformer($property, null);
        self::assertEquals($expectedValue, $transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            'default' => [
                'id',
                $this->createEntity(1),
                1
            ],
            'empty' => [
                'id',
                null,
                null
            ],
        ];
    }

    public function testTransformFailsWhenValueInNotAnArray(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "object", "string" given');

        $transformer = $this->getTransformer('id', null);
        $transformer->transform('invalid value');
    }

    public function testReverseTransformEmpty(): void
    {
        $transformer = $this->getTransformer('id', null);
        self::assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransform(): void
    {
        $entity = $this->createEntity(1);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = $this->getTransformer('id', null);
        self::assertEquals($entity, $transformer->reverseTransform(1));
    }

    public function testReverseTransformFailsNotFindEntity(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The value "1" does not exist or not unique.');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = $this->getTransformer('id', null);
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformQueryBuilder(): void
    {
        $entity = $this->createEntity(1);

        $repository = $this->createMock(EntityRepository::class);

        $self = $this;
        $callback = function ($pRepository, $pId) use ($self, $repository, $entity) {
            $self->assertEquals($repository, $pRepository);
            $self->assertEquals(1, $pId);

            $query = $self->createMock(AbstractQuery::class);
            $query->expects($self->once())
                ->method('execute')
                ->willReturn([$entity]);

            $qb = $self->createMock(QueryBuilder::class);
            $qb->expects($self->once())
                ->method('getQuery')
                ->willReturn($query);

            return $qb;
        };

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = $this->getTransformer('id', $callback);
        self::assertEquals($entity, $transformer->reverseTransform(1));
    }

    public function testReverseTransformTransformationFailedException(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The value "1" does not exist or not unique.');

        $repository = $this->createMock(EntityRepository::class);

        $self = $this;

        $callback = function () use ($self) {
            $query = $self->createMock(AbstractQuery::class);
            $query->expects($self->once())
                ->method('execute')
                ->willReturn([]);

            $qb = $self->createMock(QueryBuilder::class);
            $qb->expects($self->once())
                ->method('getQuery')
                ->willReturn($query);

            return $qb;
        };

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = $this->getTransformer('id', $callback);
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformQueryBuilderUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Doctrine\ORM\QueryBuilder", "null" given');

        $entity = $this->createEntity(1);

        $repository = $this->createMock(EntityRepository::class);

        $callback = function () {
            return null;
        };

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = $this->getTransformer('id', $callback);
        self::assertEquals($entity, $transformer->reverseTransform(1));
    }

    public function testPropertyConstruction(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $transformer = $this->getTransformer(null, null);
        self::assertEquals(1, $transformer->transform($this->createEntity(1)));
    }

    public function testPropertyConstructionException(): void
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Cannot get id property path of entity. "TestClass" has composite primary key.');

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::once())
            ->method('getSingleIdentifierFieldName')
            ->willThrowException(new MappingException('Exception'));
        $this->em->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $transformer = $this->getTransformer(null, null);
        $transformer->transform(new \stdClass());
    }

    public function testCallbackException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", "string" given');

        $this->getTransformer('id', 'uncallable');
    }
}
