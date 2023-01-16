<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityBundle\Event\PreloadEntityEvent;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PreloadingManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var EntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityAliasResolver;

    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var PreloadingManager */
    private $manager;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityAliasResolver = $this->createMock(EntityAliasResolver::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->manager = new PreloadingManager(
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->entityAliasResolver,
            $this->propertyAccessor
        );
    }

    public function testPreloadInEntitiesWhenNoEntities(): void
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityClass');

        $this->manager->preloadInEntities([], [], []);
    }

    public function testPreloadInEntitiesWhenNoFieldsToPreload(): void
    {
        $entities = [new \stdClass(), new \stdClass()];
        $fieldsToPreload = [];
        $context = [];
        $eventName = 'oro_entity.preload_entity.stdclass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->entityAliasResolver->expects($this->once())
            ->method('getAlias')
            ->with(\stdClass::class)
            ->willReturn('stdclass');

        $event = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, $eventName);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($this->createMock(ClassMetadata::class));

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }

    public function testPreloadInEntitiesWhenNoAssociation(): void
    {
        $entities = [new \stdClass(), new \stdClass()];
        $invalidField = 'invalidField';
        $fieldsToPreload = [$invalidField => []];
        $context = [];

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);

        $entityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($invalidField)
            ->willReturn(false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Field invalidField of entity stdClass is not an association which can be preloaded'
        );

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }

    public function testPreloadInEntitiesWhenNoSubFields(): void
    {
        $entities = [new \stdClass(), new \stdClass()];
        $targetField = 'sampleField';
        $fieldsToPreload = [$targetField => []];
        $context = [];
        $eventName = 'oro_entity.preload_entity.stdclass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->entityAliasResolver->expects($this->once())
            ->method('getAlias')
            ->with(\stdClass::class)
            ->willReturn('stdclass');

        $event = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, $eventName);

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);

        $entityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($targetField)
            ->willReturn(true);

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }

    public function testPreloadInEntitiesWhenToOne(): void
    {
        $entities = [new \stdClass(), new \stdClass()];
        $targetField = 'sampleField';
        $subField = 'sampleSubField';
        $fieldsToPreload = [$targetField => [$subField => []]];
        $context = [];
        $eventName1 = 'oro_entity.preload_entity.stdclass';
        $eventName2 = 'oro_entity.preload_entity.substdclass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->entityAliasResolver->expects($this->atLeastOnce())
            ->method('getAlias')
            ->withConsecutive([\stdClass::class], [\stdClass::class])
            ->willReturnOnConsecutiveCalls('stdclass', 'substdclass');

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);

        $entityMetadata->expects($this->exactly(2))
            ->method('hasAssociation')
            ->withConsecutive([$targetField], [$subField])
            ->willReturnOnConsecutiveCalls(true, true);

        $assocMapping = ['type' => ClassMetadata::MANY_TO_ONE];

        $entityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($targetField)
            ->willReturn($assocMapping);

        $targetFieldEntity1 = new \stdClass();
        $targetFieldEntity2 = new \stdClass();
        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive([$entities[0], $targetField], [$entities[1], $targetField])
            ->willReturnOnConsecutiveCalls($targetFieldEntity1, $targetFieldEntity2);

        $entityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with($targetField)
            ->willReturn(\stdClass::class);

        $event1 = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $event2 = new PreloadEntityEvent(
            [$targetFieldEntity1, $targetFieldEntity2],
            $fieldsToPreload[$targetField],
            $context
        );
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive([$event1, $eventName1], [$event2, $eventName2]);

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }

    public function testPreloadInEntitiesWhenToOneAndNoEntity(): void
    {
        $entities = [new \stdClass()];
        $targetField = 'sampleField';
        $subField = 'sampleSubField';
        $fieldsToPreload = [$targetField => [$subField => []]];
        $context = [];
        $eventName1 = 'oro_entity.preload_entity.stdclass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->entityAliasResolver->expects($this->atLeastOnce())
            ->method('getAlias')
            ->withConsecutive([\stdClass::class], [\stdClass::class])
            ->willReturnOnConsecutiveCalls('stdclass', 'substdclass');

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);

        $entityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($targetField)
            ->willReturn(true);

        $assocMapping = ['type' => ClassMetadata::MANY_TO_ONE];

        $entityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($targetField)
            ->willReturn($assocMapping);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($entities[0], $targetField)
            ->willReturn(null);

        $entityMetadata->expects($this->never())
            ->method('getAssociationTargetClass');

        $event1 = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event1, $eventName1);

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }

    public function testPreloadInEntitiesWhenToMany(): void
    {
        $entities = [new \stdClass(), new \stdClass()];
        $targetField = 'sampleField';
        $subField = 'sampleSubField';
        $fieldsToPreload = [$targetField => [$subField => []]];
        $context = [];
        $eventName1 = 'oro_entity.preload_entity.stdclass';
        $eventName2 = 'oro_entity.preload_entity.substdclass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->entityAliasResolver->expects($this->atLeastOnce())
            ->method('getAlias')
            ->withConsecutive([\stdClass::class], [\stdClass::class])
            ->willReturnOnConsecutiveCalls('stdclass', 'substdclass');

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);

        $entityMetadata->expects($this->exactly(2))
            ->method('hasAssociation')
            ->withConsecutive([$targetField], [$subField])
            ->willReturnOnConsecutiveCalls(true, true);

        $assocMapping = ['type' => ClassMetadata::ONE_TO_MANY];

        $entityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($targetField)
            ->willReturn($assocMapping);

        $targetFieldEntity1 = new \stdClass();
        $targetFieldEntity2 = new \stdClass();
        $targetFieldEntity3 = new \stdClass();
        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive([$entities[0], $targetField], [$entities[1], $targetField])
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([$targetFieldEntity1, $targetFieldEntity2]),
                new ArrayCollection([$targetFieldEntity3])
            );

        $entityMetadata->expects($this->once())
            ->method('getAssociationTargetClass')
            ->with($targetField)
            ->willReturn(\stdClass::class);

        $event1 = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $event2 = new PreloadEntityEvent(
            [$targetFieldEntity1, $targetFieldEntity2, $targetFieldEntity3],
            $fieldsToPreload[$targetField],
            $context
        );
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive([$event1, $eventName1], [$event2, $eventName2]);

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }

    public function testPreloadInEntitiesWhenToManyAndEmptyCollection(): void
    {
        $entities = [new \stdClass()];
        $targetField = 'sampleField';
        $subField = 'sampleSubField';
        $fieldsToPreload = [$targetField => [$subField => []]];
        $context = [];
        $eventName1 = 'oro_entity.preload_entity.stdclass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->entityAliasResolver->expects($this->atLeastOnce())
            ->method('getAlias')
            ->withConsecutive([\stdClass::class], [\stdClass::class])
            ->willReturnOnConsecutiveCalls('stdclass', 'substdclass');

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);

        $entityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($targetField)
            ->willReturn(true);

        $assocMapping = ['type' => ClassMetadata::ONE_TO_MANY];

        $entityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($targetField)
            ->willReturn($assocMapping);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->withConsecutive([$entities[0], $targetField])
            ->willReturn(new ArrayCollection());

        $entityMetadata->expects($this->never())
            ->method('getAssociationTargetClass');

        $event1 = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event1, $eventName1);

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }

    public function testPreloadInEntitiesWhenToManyAndNotInitialized(): void
    {
        $entities = [new \stdClass()];
        $targetField = 'sampleField';
        $subField = 'sampleSubField';
        $fieldsToPreload = [$targetField => [$subField => []]];
        $context = [];
        $eventName1 = 'oro_entity.preload_entity.stdclass';

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entities[0])
            ->willReturn(\stdClass::class);

        $this->entityAliasResolver->expects($this->atLeastOnce())
            ->method('getAlias')
            ->withConsecutive([\stdClass::class], [\stdClass::class])
            ->willReturnOnConsecutiveCalls('stdclass', 'substdclass');

        $entityMetadata = $this->createMock(ClassMetadata::class);
        $this->doctrineHelper->expects($this->atLeastOnce())
            ->method('getEntityMetadataForClass')
            ->with(\stdClass::class)
            ->willReturn($entityMetadata);

        $entityMetadata->expects($this->once())
            ->method('hasAssociation')
            ->with($targetField)
            ->willReturn(true);

        $assocMapping = ['type' => ClassMetadata::ONE_TO_MANY];

        $entityMetadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($targetField)
            ->willReturn($assocMapping);

        $collection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ClassMetadata::class),
            new ArrayCollection([new \stdClass()])
        );
        $collection->setInitialized(false);

        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->withConsecutive([$entities[0], $targetField])
            ->willReturn($collection);

        $entityMetadata->expects($this->never())
            ->method('getAssociationTargetClass');

        $event1 = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event1, $eventName1);

        $this->manager->preloadInEntities($entities, $fieldsToPreload, $context);
    }
}
