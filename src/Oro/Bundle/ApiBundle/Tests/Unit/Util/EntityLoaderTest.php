<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Util\EntityLoader;

class EntityLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var EntityLoader */
    private $entityLoader;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->entityLoader = new EntityLoader($this->doctrine);
    }

    public function testFindEntityWithoutMetadata()
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with($entityId)
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId)
        );
    }

    public function testFindEntityForEntityWithSingleIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with($entityId)
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityForEntityWithRenamedSingleIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['renamedId']);
        $metadata->addField(new FieldMetadata('renamedId'))->setPropertyPath('id');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with($entityId)
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherFieldIsUsedAsIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $expectedCriteria = ['field1' => $entityId];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['field1']);
        $metadata->addField(new FieldMetadata('field1'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findBy')
            ->with($expectedCriteria)
            ->willReturn([$entity]);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherFieldIsUsedAsIdentifierAndEntityNotFound()
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;

        $expectedCriteria = ['field1' => $entityId];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['field1']);
        $metadata->addField(new FieldMetadata('field1'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findBy')
            ->with($expectedCriteria)
            ->willReturn([]);

        self::assertNull(
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherRenamedFieldIsUsedAsIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = 1;
        $entity = new \stdClass();

        $expectedCriteria = ['field1' => $entityId];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['renamedField1']);
        $metadata->addField(new FieldMetadata('renamedField1'))->setPropertyPath('field1');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findBy')
            ->with($expectedCriteria)
            ->willReturn([$entity]);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityForEntityWithCompositeIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = ['id1' => 1, 'id2' => 2];
        $entity = new \stdClass();

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'));
        $metadata->addField(new FieldMetadata('id2'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with($entityId)
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityForEntityWithRenamedCompositeIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = ['renamedId1' => 1, 'renamedId2' => 2];
        $entity = new \stdClass();

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['renamedId1', 'renamedId2']);
        $metadata->addField(new FieldMetadata('renamedId1'))->setPropertyPath('id1');
        $metadata->addField(new FieldMetadata('renamedId2'))->setPropertyPath('id2');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id1', 'id2']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('find')
            ->with(['id1' => 1, 'id2' => 2])
            ->willReturn($entity);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherFieldsIsUsedAsIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = ['field1' => 1, 'field2' => 2];
        $entity = new \stdClass();

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['field1', 'field2']);
        $metadata->addField(new FieldMetadata('field1'));
        $metadata->addField(new FieldMetadata('field2'));

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findBy')
            ->with($entityId)
            ->willReturn([$entity]);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }

    public function testFindEntityWhenAnotherRenamedFieldsIsUsedAsIdentifier()
    {
        $entityClass = 'Test\Entity';
        $entityId = ['renamedField1' => 1, 'renamedField2' => 2];
        $entity = new \stdClass();

        $expectedCriteria = ['field1' => 1, 'field2' => 2];

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['renamedField1', 'renamedField2']);
        $metadata->addField(new FieldMetadata('renamedField1'))->setPropertyPath('field1');
        $metadata->addField(new FieldMetadata('renamedField2'))->setPropertyPath('field2');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getIdentifierFieldNames')
            ->willReturn(['field1', 'field3']);

        $manager = $this->createMock(EntityManager::class);
        $repo = $this->createMock(EntityRepository::class);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($manager);
        $manager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);
        $manager->expects(self::once())
            ->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findBy')
            ->with($expectedCriteria)
            ->willReturn([$entity]);

        self::assertSame(
            $entity,
            $this->entityLoader->findEntity($entityClass, $entityId, $metadata)
        );
    }
}
