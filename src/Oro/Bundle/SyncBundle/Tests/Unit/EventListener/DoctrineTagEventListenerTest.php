<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SyncBundle\Content\TagGeneratorChain;
use Oro\Bundle\SyncBundle\Content\TopicSender;
use Oro\Bundle\SyncBundle\EventListener\DoctrineTagEventListener;
use Oro\Bundle\TestFrameworkBundle\Entity;

class DoctrineTagEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager */
    private $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject|UnitOfWork */
    private $uow;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TopicSender */
    private $sender;

    /** @var DoctrineTagEventListener */
    private $eventListener;

    public function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->sender = $this->createMock(TopicSender::class);
        $this->eventListener = new DoctrineTagEventListener($this->sender, true);
    }

    /**
     * @param object $owner
     *
     * @return PersistentCollection
     */
    private function createPersistentCollection($owner)
    {
        $coll = new PersistentCollection($this->em, 'TestClass', new ArrayCollection());
        $coll->setOwner($owner, ['inversedBy' => null, 'mappedBy' => 'test']);

        return $coll;
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed  $value
     */
    private function setPrivateProperty($object, $property, $value)
    {
        $refl = new \ReflectionClass($object);
        $prop = $refl->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    public function testOnFlushForScheduledEntityInsertions()
    {
        $entity1 = new Entity\TestDepartment();
        $entity2 = new Entity\TestEmployee();
        $entity3 = new Entity\TestProduct();

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$entity1, $entity2, $entity3]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $generator = $this->createMock(TagGeneratorChain::class);
        $this->sender->expects(self::any())
            ->method('getGenerator')
            ->willReturn($generator);
        $generator->expects(self::at(0))
            ->method('generate')
            ->with(self::isInstanceOf($entity1), true)
            ->willReturn(['entity1Tag']);
        $generator->expects(self::at(1))
            ->method('generate')
            ->with(self::isInstanceOf($entity2), true)
            ->willReturn(['entity2Tag']);

        $event = new OnFlushEventArgs($this->em);
        $this->eventListener->onFlush($event);

        self::assertAttributeEquals(
            ['entity1Tag', 'entity2Tag'],
            'collectedTags',
            $this->eventListener
        );
        self::assertAttributeEquals(
            [spl_object_hash($entity1) => true, spl_object_hash($entity2) => true],
            'processedEntities',
            $this->eventListener
        );
    }

    public function testOnFlushForScheduledEntityDeletions()
    {
        $entity1 = new Entity\TestDepartment();
        $entity2 = new Entity\TestEmployee();
        $entity3 = new Entity\TestProduct();

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$entity1, $entity2, $entity3]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $generator = $this->createMock(TagGeneratorChain::class);
        $this->sender->expects(self::any())
            ->method('getGenerator')
            ->willReturn($generator);
        $generator->expects(self::at(0))
            ->method('generate')
            ->with(self::isInstanceOf($entity1), true)
            ->willReturn(['entity1Tag']);
        $generator->expects(self::at(1))
            ->method('generate')
            ->with(self::isInstanceOf($entity2), true)
            ->willReturn(['entity2Tag']);

        $event = new OnFlushEventArgs($this->em);
        $this->eventListener->onFlush($event);

        self::assertAttributeEquals(
            ['entity1Tag', 'entity2Tag'],
            'collectedTags',
            $this->eventListener
        );
        self::assertAttributeEquals(
            [spl_object_hash($entity1) => true, spl_object_hash($entity2) => true],
            'processedEntities',
            $this->eventListener
        );
    }

    public function testOnFlushForScheduledEntityUpdates()
    {
        $entity1 = new Entity\TestDepartment();
        $entity2 = new Entity\TestEmployee();
        $entity3 = new Entity\TestProduct();

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity1, $entity2, $entity3]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $generator = $this->createMock(TagGeneratorChain::class);
        $this->sender->expects(self::any())
            ->method('getGenerator')
            ->willReturn($generator);
        $generator->expects(self::at(0))
            ->method('generate')
            ->with(self::isInstanceOf($entity1), false)
            ->willReturn(['entity1Tag']);
        $generator->expects(self::at(1))
            ->method('generate')
            ->with(self::isInstanceOf($entity2), false)
            ->willReturn(['entity2Tag']);

        $event = new OnFlushEventArgs($this->em);
        $this->eventListener->onFlush($event);

        self::assertAttributeEquals(
            ['entity1Tag', 'entity2Tag'],
            'collectedTags',
            $this->eventListener
        );
        self::assertAttributeEquals(
            [spl_object_hash($entity1) => true, spl_object_hash($entity2) => true],
            'processedEntities',
            $this->eventListener
        );
    }

    public function testOnFlushForScheduledCollectionDeletions()
    {
        $entity1 = new Entity\TestDepartment();
        $coll1 = $this->createPersistentCollection($entity1);
        $entity2 = new Entity\TestEmployee();
        $coll2 = $this->createPersistentCollection($entity2);
        $entity3 = new Entity\TestProduct();
        $coll3 = $this->createPersistentCollection($entity3);

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity1]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([$coll1, $coll2, $coll3]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $generator = $this->createMock(TagGeneratorChain::class);
        $this->sender->expects(self::any())
            ->method('getGenerator')
            ->willReturn($generator);
        $generator->expects(self::at(0))
            ->method('generate')
            ->with(self::isInstanceOf($entity1), false)
            ->willReturn(['entity1Tag']);
        $generator->expects(self::at(1))
            ->method('generate')
            ->with(self::isInstanceOf($entity2), false)
            ->willReturn(['entity2Tag']);

        $event = new OnFlushEventArgs($this->em);
        $this->eventListener->onFlush($event);

        self::assertAttributeEquals(
            ['entity1Tag', 'entity2Tag'],
            'collectedTags',
            $this->eventListener
        );
        self::assertAttributeEquals(
            [spl_object_hash($entity1) => true, spl_object_hash($entity2) => true],
            'processedEntities',
            $this->eventListener
        );
    }

    public function testOnFlushForScheduledCollectionUpdates()
    {
        $entity1 = new Entity\TestDepartment();
        $coll1 = $this->createPersistentCollection($entity1);
        $entity2 = new Entity\TestEmployee();
        $coll2 = $this->createPersistentCollection($entity2);
        $entity3 = new Entity\TestProduct();
        $coll3 = $this->createPersistentCollection($entity3);

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity1]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([$coll1, $coll2, $coll3]);

        $generator = $this->createMock(TagGeneratorChain::class);
        $this->sender->expects(self::any())
            ->method('getGenerator')
            ->willReturn($generator);
        $generator->expects(self::at(0))
            ->method('generate')
            ->with(self::isInstanceOf($entity1), false)
            ->willReturn(['entity1Tag']);
        $generator->expects(self::at(1))
            ->method('generate')
            ->with(self::isInstanceOf($entity2), false)
            ->willReturn(['entity2Tag']);

        $event = new OnFlushEventArgs($this->em);
        $this->eventListener->onFlush($event);

        self::assertAttributeEquals(
            ['entity1Tag', 'entity2Tag'],
            'collectedTags',
            $this->eventListener
        );
        self::assertAttributeEquals(
            [spl_object_hash($entity1) => true, spl_object_hash($entity2) => true],
            'processedEntities',
            $this->eventListener
        );
    }

    public function testPostFlush()
    {
        $this->setPrivateProperty(
            $this->eventListener,
            'collectedTags',
            ['duplicatedTag', 'anotherTag', 'duplicatedTag']
        );
        $this->setPrivateProperty(
            $this->eventListener,
            'processedEntities',
            ['entity1' => true, 'entity2' => true]
        );

        $this->sender->expects(self::once())
            ->method('send')
            ->with(['duplicatedTag', 'anotherTag']);

        $event = new PostFlushEventArgs($this->em);
        $this->eventListener->postFlush($event);

        self::assertAttributeEquals(
            [],
            'collectedTags',
            $this->eventListener
        );
        self::assertAttributeEquals(
            [],
            'processedEntities',
            $this->eventListener
        );
    }
}
