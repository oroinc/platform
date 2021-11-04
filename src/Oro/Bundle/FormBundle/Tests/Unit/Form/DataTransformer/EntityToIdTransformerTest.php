<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Exception\FormException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityToIdTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(string $property, ?object $value, ?int $expectedValue)
    {
        $transformer = new EntityToIdTransformer($this->entityManager, 'TestClass', $property, null);
        $this->assertEquals($expectedValue, $transformer->transform($value));
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

    public function testTransformFailsWhenValueInNotAnArray()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "object", "string" given');

        $transformer = new EntityToIdTransformer($this->entityManager, 'TestClass', 'id', null);
        $transformer->transform('invalid value');
    }

    public function testReverseTransformEmpty()
    {
        $transformer = new EntityToIdTransformer($this->entityManager, 'TestClass', 'id', null);
        $this->assertNull($transformer->reverseTransform(''));
    }

    public function testReverseTransform()
    {
        $entity = $this->createEntity(1);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($entity);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', null);
        $this->assertEquals($entity, $transformer->reverseTransform(1));
    }

    public function testReverseTransformFailsNotFindEntity()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The value "1" does not exist or not unique.');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', null);
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformQueryBuilder()
    {
        $entity = $this->createEntity(1);

        $repository = $this->createMock(EntityRepository::class);

        $self= $this;
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

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', $callback);
        $this->assertEquals($entity, $transformer->reverseTransform(1));
    }

    public function testReverseTransformTransformationFailedException()
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

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', $callback, true);
        $transformer->reverseTransform(1);
    }

    public function testReverseTransformQueryBuilderUnexpectedTypeException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "Doctrine\ORM\QueryBuilder", "null" given');

        $entity = $this->createEntity(1);

        $repository = $this->createMock(EntityRepository::class);

        $callback = function () {
            return null;
        };

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('TestClass')
            ->willReturn($repository);

        $transformer = new EntityToIdTransformer($em, 'TestClass', 'id', $callback);
        $this->assertEquals($entity, $transformer->reverseTransform(1));
    }

    public function testPropertyConstruction()
    {
        $em = $this->createMock(EntityManager::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        new EntityToIdTransformer($em, 'TestClass', null, null);
    }

    public function testPropertyConstructionException()
    {
        $this->expectException(FormException::class);
        $this->expectExceptionMessage('Cannot get id property path of entity. "TestClass" has composite primary key.');

        $em = $this->createMock(EntityManager::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willThrowException(new MappingException('Exception'));
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        new EntityToIdTransformer($em, 'TestClass', null, null);
    }

    public function testCallbackException()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", "string" given');

        new EntityToIdTransformer($this->entityManager, 'TestClass', 'id', 'uncallable');
    }

    private function createEntity(int $id): \stdClass
    {
        $result = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $result->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $result;
    }
}
