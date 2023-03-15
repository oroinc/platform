<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Writer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Writer\Stub\EntityStub;
use Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer;

class EntityDetachFixerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelper;

    /** @var EntityDetachFixer */
    private $fixer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->fixer = new EntityDetachFixer(
            $doctrineHelper,
            $this->fieldHelper,
            PropertyAccess::createPropertyAccessor()
        );
    }

    public function testFixEntityAssociationFieldsLevel()
    {
        $entity = new \stdClass();

        $this->fieldHelper->expects($this->never())
            ->method('getRelations');
        $this->fixer->fixEntityAssociationFields($entity, -1);
    }

    public function testEntityWithoutRelations()
    {
        $entity = new \stdClass();

        $this->fieldHelper->expects($this->once())
            ->method('getRelations')
            ->with(get_class($entity))
            ->willReturn([]);

        $this->entityManager->expects($this->never())
            ->method('getUnitOfWork');

        $this->fixer->fixEntityAssociationFields($entity);
    }

    public function testFixEntityAssociationFields()
    {
        $entity = new EntityStub();
        $entity->setEntity(new EntityStub())
            ->setNewCollection(new ArrayCollection([new EntityStub()]))
            ->setDirtyPersistentCollection($this->createPersistentCollection(true, false))
            ->setInitializedPersistentCollection($this->createPersistentCollection(false, true))
            ->setCleanNotInitializedPersistentCollection($this->createPersistentCollection(false, false));

        $this->fieldHelper->expects($this->once())
            ->method('getRelations')
            ->with(get_class($entity))
            ->willReturn(
                [
                    ['name' => 'entity'],
                    ['name' => 'newCollection'],
                    ['name' => 'dirtyPersistentCollection'],
                    ['name' => 'initializedPersistentCollection'],
                    ['name' => 'cleanNotInitializedPersistentCollection'],
                    ['name' => 'notReadable']
                ]
            );

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->exactly(4))
            ->method('getIdentifierValues')
            ->withAnyParameters()
            ->willReturn('id');

        $this->entityManager->expects($this->exactly(4))
            ->method('getClassMetadata')
            ->with(EntityStub::class)
            ->willReturn($metadata);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->exactly(4))
            ->method('getEntityState')
            ->willReturn(UnitOfWork::STATE_DETACHED);

        // 4 entity check + 2 check inside PersistentCollection on set entity
        $this->entityManager->expects($this->exactly(6))
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->entityManager->expects($this->exactly(4))
            ->method('getReference')
            ->with(EntityStub::class, 'id')
            ->willReturnCallback(function () {
                $entity = new EntityStub();
                $entity->reloaded = true;

                return $entity;
            });

        $uow->expects($this->never())
            ->method('loadCollection');

        $this->fixer->fixEntityAssociationFields($entity);

        $this->assertTrue($entity->getEntity()->reloaded);

        $this->assertCount(1, $entity->getNewCollection());
        $this->assertTrue($entity->getNewCollection()->first()->reloaded);

        $this->assertCount(1, $entity->getDirtyPersistentCollection());
        $this->assertTrue($entity->getDirtyPersistentCollection()->first()->reloaded);

        $this->assertCount(1, $entity->getInitializedPersistentCollection());
        $this->assertTrue($entity->getInitializedPersistentCollection()->first()->reloaded);

        $this->assertCount(1, $entity->getCleanNotInitializedPersistentCollection());
        $this->assertFalse($entity->getCleanNotInitializedPersistentCollection()->first()->reloaded);
    }

    private function createPersistentCollection(bool $isDirty, bool $isInitialized): PersistentCollection
    {
        $changedPersistentCollection = new PersistentCollection(
            $this->entityManager,
            null,
            new ArrayCollection([new EntityStub()])
        );

        $changedPersistentCollection->setDirty($isDirty);
        $changedPersistentCollection->setInitialized($isInitialized);

        return $changedPersistentCollection;
    }
}
