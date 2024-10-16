<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var QueryHintResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryHintResolver;

    /** @var EntityLoader */
    private $entityLoader;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->queryHintResolver = $this->createMock(QueryHintResolverInterface::class);

        $this->entityLoader = new EntityLoader($this->doctrineHelper, $this->queryHintResolver);
    }

    public function testFindEntityWithoutMetadata(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, null)
        );
    }

    public function testFindEntityWithoutMetadataWhenEntityNotFound(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('find')
            ->with($entityClass, $entityId)
            ->willReturn(null);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertNull(
            $this->entityLoader->findEntity($entityClass, $entityId, null)
        );
    }

    public function testFindEntityForEntityWithSingleIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $em->expects(self::once())
            ->method('find')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityForEntityWithRenamedSingleIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['renamedId']);
        $metadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $em->expects(self::once())
            ->method('find')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherFieldIsUsedAsIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['field1']);
        $metadata->addField(new FieldMetadata('field1'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('andWhere')
            ->with('e.field1 = :field1')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('field1', $entityId)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherFieldIsUsedAsIdentifierAndEntityNotFound(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['field1']);
        $metadata->addField(new FieldMetadata('field1'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('andWhere')
            ->with('e.field1 = :field1')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('field1', $entityId)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertNull(
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherFieldIsUsedAsIdentifierAndSeveralEntitiesFound(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $entityClass = 'Test\Entity';
        $entityId = 1;

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['field1']);
        $metadata->addField(new FieldMetadata('field1'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('andWhere')
            ->with('e.field1 = :field1')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('field1', $entityId)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willThrowException(new NonUniqueResultException());

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        $this->entityLoader->findEntity($entityClass, $entityId, $metadata);
    }

    public function testFindEntityWhenAnotherRenamedFieldIsUsedAsIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['renamedField1']);
        $metadata->addField(new FieldMetadata('renamedField1'))->setPropertyPath('field1');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);
        $qb->expects(self::once())
            ->method('andWhere')
            ->with('e.field1 = :field1')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('setParameter')
            ->with('field1', $entityId)
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityForEntityWithCompositeIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = ['id1' => 1, 'id2' => 2];
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'));
        $metadata->addField(new FieldMetadata('id2'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $em->expects(self::once())
            ->method('find')
            ->with($entityClass, $entityId)
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityForEntityWithRenamedCompositeIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = ['renamedId1' => 1, 'renamedId2' => 2];
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['renamedId1', 'renamedId2']);
        $metadata->addField(new FieldMetadata('renamedId1'))->setPropertyPath('id1');
        $metadata->addField(new FieldMetadata('renamedId2'))->setPropertyPath('id2');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $em->expects(self::once())
            ->method('find')
            ->with($entityClass, ['id1' => 1, 'id2' => 2])
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenOtherFieldsIsUsedAsIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = ['field1' => 1, 'field2' => 2];
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['field1', 'field2']);
        $metadata->addField(new FieldMetadata('field1'));
        $metadata->addField(new FieldMetadata('field2'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);
        $qb->expects(self::exactly(2))
            ->method('andWhere')
            ->withConsecutive(['e.field1 = :field1'], ['e.field2 = :field2'])
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['field1', $entityId['field1']], ['field2', $entityId['field2']])
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenOtherRenamedFieldsIsUsedAsIdentifier(): void
    {
        $entityClass = 'Test\Entity';
        $entityId = ['renamedField1' => 1, 'renamedField2' => 2];
        $entity = new \stdClass();

        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['renamedField1', 'renamedField2']);
        $metadata->addField(new FieldMetadata('renamedField1'))->setPropertyPath('field1');
        $metadata->addField(new FieldMetadata('renamedField2'))->setPropertyPath('field2');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['field1', 'field3']);

        $em = $this->createMock(EntityManagerInterface::class);
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with($entityClass)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with($entityClass, 'e')
            ->willReturn($qb);
        $qb->expects(self::exactly(2))
            ->method('andWhere')
            ->withConsecutive(['e.field1 = :field1'], ['e.field2 = :field2'])
            ->willReturnSelf();
        $qb->expects(self::exactly(2))
            ->method('setParameter')
            ->withConsecutive(['field1', $entityId['renamedField1']], ['field2', $entityId['renamedField2']])
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($entity);

        $this->queryHintResolver->expects(self::never())
            ->method('resolveHints');

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }
}
