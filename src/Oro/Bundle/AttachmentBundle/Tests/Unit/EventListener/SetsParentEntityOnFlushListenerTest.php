<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\SetsParentEntityOnFlushListener;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Stub\ParentEntity;
use Oro\Component\PropertyAccess\PropertyAccessor;

class SetsParentEntityOnFlushListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var SetsParentEntityOnFlushListener */
    private $listener;

    protected function setUp()
    {
        $this->propertyAccessor = new PropertyAccessor();
        $this->listener = new SetsParentEntityOnFlushListener($this->propertyAccessor);
    }

    public function testOnFlushWhenCompositeId(): void
    {
        $eventOnFlush = $this->createMock(OnFlushEventArgs::class);
        [$entityManager, $unitOfWork] = $this->mockEntityManager($eventOnFlush);

        $unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([new \stdClass()]);

        $entityManager
            ->method('getClassMetadata')
            ->willReturn($classMetadata = $this->createMock(ClassMetadata::class));

        $classMetadata
            ->method('getIdentifier')
            ->willReturn(['id', 'name']);

        $unitOfWork
            ->expects(self::never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onFlush($eventOnFlush);
    }

    public function testOnFlush(): void
    {
        $eventOnFlush = $this->createMock(OnFlushEventArgs::class);
        [$entityManager, $unitOfWork] = $this->mockEntityManager($eventOnFlush);

        $fileToInsert = (new File())->setFilename('sample-filename');
        $fileToInsert2 = (new File())->setFilename('sample-filename2');
        $fileNotForUpdate = (new File())->setParentEntityClass($parentEntityClass = \stdClass::class);

        $entityToUpdate = $this->createEntity($id = 1, $fileToInsert, [$fileToInsert2]);
        $entityWithoutFileToUpdate = $this->createEntity(2, $fileNotForUpdate, []);
        $entityWithoutFileField = $this->createEntity(3, null, []);

        $unitOfWork
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entityToUpdate, $entityWithoutFileToUpdate, $entityWithoutFileField]);

        $entityManager
            ->method('getClassMetadata')
            ->willReturn($classMetadata = $this->createMock(ClassMetadata::class));

        $classMetadata
            ->method('getIdentifier')
            ->willReturn(['id']);

        $classMetadata
            ->method('getAssociationMappings')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'isOwningSide' => true,
                        'targetEntity' => File::class,
                        'fieldName' => $fieldName = 'file',
                        'type' => ClassMetadata::MANY_TO_ONE,
                    ],
                    [
                        'isOwningSide' => true,
                        'targetEntity' => File::class,
                        'fieldName' => $fieldNameToMany = 'files',
                        'type' => ClassMetadata::ONE_TO_MANY,
                    ],
                ],
                [
                    [
                        'isOwningSide' => true,
                        'targetEntity' => File::class,
                        'fieldName' => 'file',
                        'type' => ClassMetadata::MANY_TO_ONE,
                    ],
                ],
                [['isOwningSide' => false]]
            );

        $unitOfWork
            ->expects(self::exactly(2))
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->onFlush($eventOnFlush);

        self::assertEquals($id, $fileToInsert->getParentEntityId());
        self::assertEquals(get_class($entityToUpdate), $fileToInsert->getParentEntityClass());
        self::assertEquals($fieldName, $fileToInsert->getParentEntityFieldName());

        self::assertEquals($id, $fileToInsert2->getParentEntityId());
        self::assertEquals(get_class($entityToUpdate), $fileToInsert2->getParentEntityClass());
        self::assertEquals($fieldNameToMany, $fileToInsert2->getParentEntityFieldName());

        self::assertEquals($parentEntityClass, $fileNotForUpdate->getParentEntityClass());
    }

    public function testPrePersistPostPersist(): void
    {
        $fileToInsert = (new File())->setFilename('sample-filename');
        $fileToInsert2 = (new File())->setFilename('sample-filename2');

        $entityToInsert = $this->createEntity($id = 1, $fileToInsert, [$fileToInsert2]);

        $eventPrePersist = $this->mockLifecycleEvent($entityToInsert);
        [$entityManager] = $this->mockEntityManager($eventPrePersist);

        $entityManager
            ->method('getClassMetadata')
            ->with(\get_class($entityToInsert))
            ->willReturn($classMetadata = $this->createMock(ClassMetadata::class));

        $classMetadata
            ->method('getIdentifier')
            ->willReturn(['id']);

        $classMetadata
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'isOwningSide' => true,
                    'targetEntity' => File::class,
                    'fieldName' => $fieldName = 'file',
                    'type' => ClassMetadata::MANY_TO_ONE,
                ],
                [
                    'isOwningSide' => true,
                    'targetEntity' => File::class,
                    'fieldName' => $fieldNameToMany = 'files',
                    'type' => ClassMetadata::ONE_TO_MANY,
                ],
            ]);

        $this->listener->prePersist($eventPrePersist);

        $eventPostPersist = $this->mockLifecycleEvent($entityToInsert);
        [$entityManager, $unitOfWork] = $this->mockEntityManager($eventPostPersist);

        $entityManager
            ->method('getClassMetadata')
            ->withConsecutive([\get_class($entityToInsert)], [File::class])
            ->willReturn($classMetadata = $this->createMock(ClassMetadata::class));

        $classMetadata
            ->method('getIdentifier')
            ->willReturn(['id']);

        $unitOfWork
            ->expects(self::exactly(2))
            ->method('scheduleExtraUpdate');

        $unitOfWork
            ->expects(self::exactly(2))
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->postPersist($eventPostPersist);

        self::assertEquals($id, $fileToInsert->getParentEntityId());
        self::assertEquals(get_class($entityToInsert), $fileToInsert->getParentEntityClass());
        self::assertEquals($fieldName, $fileToInsert->getParentEntityFieldName());

        self::assertEquals($id, $fileToInsert2->getParentEntityId());
        self::assertEquals(get_class($entityToInsert), $fileToInsert2->getParentEntityClass());
        self::assertEquals($fieldNameToMany, $fileToInsert2->getParentEntityFieldName());

        // Checks that persist and flush will not be called again.
        $this->listener->postPersist($eventPostPersist);
    }

    public function testPrePersistPostPersistWhenNoFileToUpdate(): void
    {
        $fileNotForUpdate = (new File())->setParentEntityClass($parentEntityClass = \stdClass::class);
        $entityWithFileNotForUpdate = $this->createEntity(2, $fileNotForUpdate, []);

        $eventPrePersist = $this->mockLifecycleEvent($entityWithFileNotForUpdate);
        [$entityManager] = $this->mockEntityManager($eventPrePersist);

        $entityManager
            ->method('getClassMetadata')
            ->with(\get_class($entityWithFileNotForUpdate))
            ->willReturn($classMetadata = $this->createMock(ClassMetadata::class));

        $classMetadata
            ->method('getIdentifier')
            ->willReturn(['id']);

        $classMetadata
            ->method('getAssociationMappings')
            ->willReturn([
                [
                    'isOwningSide' => true,
                    'targetEntity' => File::class,
                    'fieldName' => $fieldName = 'file',
                    'type' => ClassMetadata::MANY_TO_ONE,
                ]
            ]);

        $this->listener->prePersist($eventPrePersist);

        $eventPostPersist = $this->mockLifecycleEvent($entityWithFileNotForUpdate);
        [$entityManager, $unitOfWork] = $this->mockEntityManager($eventPostPersist);

        $entityManager
            ->expects(self::never())
            ->method('getClassMetadata');

        $classMetadata
            ->expects(self::never())
            ->method('getIdentifier');

        $unitOfWork
            ->expects(self::never())
            ->method('scheduleExtraUpdate');

        $unitOfWork
            ->expects(self::never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->postPersist($eventPostPersist);

        self::assertEquals($parentEntityClass, $fileNotForUpdate->getParentEntityClass());
    }

    public function testPrePersistPostPersistWhenNoFileField(): void
    {
        $entityWithoutFileField = $this->createEntity(2, null, []);

        $eventPrePersist = $this->mockLifecycleEvent($entityWithoutFileField);
        [$entityManager] = $this->mockEntityManager($eventPrePersist);

        $entityManager
            ->method('getClassMetadata')
            ->with(\get_class($entityWithoutFileField))
            ->willReturn($classMetadata = $this->createMock(ClassMetadata::class));

        $classMetadata
            ->method('getIdentifier')
            ->willReturn(['id']);

        $classMetadata
            ->method('getAssociationMappings')
            ->willReturn([['isOwningSide' => true, 'targetEntity' => \stdClass::class]]);

        $this->listener->prePersist($eventPrePersist);

        $eventPostPersist = $this->mockLifecycleEvent($entityWithoutFileField);
        [$entityManager, $unitOfWork] = $this->mockEntityManager($eventPostPersist);

        $entityManager
            ->expects(self::never())
            ->method('getClassMetadata');

        $classMetadata
            ->expects(self::never())
            ->method('getIdentifier');

        $unitOfWork
            ->expects(self::never())
            ->method('scheduleExtraUpdate');

        $unitOfWork
            ->expects(self::never())
            ->method('recomputeSingleEntityChangeSet');

        $this->listener->postPersist($eventPostPersist);
    }

    /**
     * @param EventArgs|\PHPUnit\Framework\MockObject\MockObject $event
     *
     * @return array
     *  [
     *      EntityManager|\PHPUnit\Framework\MockObject\MockObject,
     *      UnitOfWork|\PHPUnit\Framework\MockObject\MockObject
     *  ]
     */
    private function mockEntityManager(\PHPUnit\Framework\MockObject\MockObject $event): array
    {
        $event
            ->method('getEntityManager')
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        return [$entityManager, $unitOfWork];
    }

    /**
     * @param object $entity
     *
     * @return LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockLifecycleEvent($entity): LifecycleEventArgs
    {
        $eventPostPersist = $this->createMock(LifecycleEventArgs::class);
        $eventPostPersist
            ->method('getEntity')
            ->willReturn($entity);

        return $eventPostPersist;
    }

    /**
     * @param int $id
     * @param File|null $file
     * @param array $files
     *
     * @return object
     */
    private function createEntity(int $id, ?File $file, array $files)
    {
        return new ParentEntity($id, $file, $files);
    }
}
