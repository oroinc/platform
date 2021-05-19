<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\SyncBundle\EventListener\DoctrineTagEventListener;
use Oro\Bundle\TestFrameworkBundle\Entity;
use PHPUnit\Framework\MockObject\MockObject;

class DoctrineTagEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagGeneratorInterface|MockObject */
    private $tagGenerator;

    /** @var EntityManager|MockObject */
    private $em;

    /** @var UnitOfWork|MockObject */
    private $uow;

    /** @var DataUpdateTopicSender|MockObject */
    private $dataUpdateTopicSender;

    /** @var DoctrineTagEventListener */
    private $eventListener;

    protected function setUp(): void
    {
        $this->tagGenerator = $this->createMock(TagGeneratorInterface::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->em->method('getUnitOfWork')->willReturn($this->uow);

        $this->dataUpdateTopicSender = $this->createMock(DataUpdateTopicSender::class);
        $this->eventListener = new DoctrineTagEventListener($this->dataUpdateTopicSender, $this->tagGenerator, true);
    }

    /**
     * @param object $owner
     *
     * @return PersistentCollection
     */
    private function createPersistentCollection($owner)
    {
        $coll = new PersistentCollection(
            $this->em,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection()
        );
        $coll->setOwner($owner, ['inversedBy' => null, 'mappedBy' => 'test']);

        return $coll;
    }

    public function testOnFlushForDisabledListener()
    {
        $this->uow->expects(self::never())->method('getScheduledEntityInsertions');
        $this->uow->expects(self::never())->method('getScheduledEntityDeletions');
        $this->uow->expects(self::never())->method('getScheduledEntityUpdates');
        $this->uow->expects(self::never())->method('getScheduledCollectionDeletions');
        $this->uow->expects(self::never())->method('getScheduledCollectionUpdates');

        $this->dataUpdateTopicSender->expects(static::never())->method('send');
        $this->tagGenerator->expects(self::never())->method('generate');

        $this->eventListener->setEnabled(false);
        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));
        $this->eventListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testOnFlushForScheduledEntityInsertions()
    {
        $entity1 = new Entity\TestDepartment();
        $entity2 = new Entity\TestEmployee();
        $entity3 = new Entity\TestProduct();

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())->method('getScheduledEntityInsertions')->willReturn([
            $entity1,
            $entity2,
            $entity3
        ]);
        $this->uow->expects(self::once())->method('getScheduledEntityDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityUpdates')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledCollectionDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->tagGenerator->expects(static::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, true, false, ['entity1Tag']],
                [$entity2, true, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(static::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testOnFlushForScheduledEntityDeletions()
    {
        $entity1 = new Entity\TestDepartment();
        $entity2 = new Entity\TestEmployee();
        $entity3 = new Entity\TestProduct();

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())->method('getScheduledEntityInsertions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityDeletions')->willReturn([
            $entity1,
            $entity2,
            $entity3
        ]);
        $this->uow->expects(self::once())->method('getScheduledEntityUpdates')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledCollectionDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->tagGenerator->expects(static::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, true, false, ['entity1Tag']],
                [$entity2, true, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(static::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testOnFlushForScheduledEntityUpdates()
    {
        $entity1 = new Entity\TestDepartment();
        $entity2 = new Entity\TestEmployee();
        $entity3 = new Entity\TestProduct();

        $this->eventListener->markSkipped(Entity\TestProduct::class);

        $this->uow->expects(self::once())->method('getScheduledEntityInsertions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityUpdates')->willReturn([
            $entity1,
            $entity2,
            $entity3
        ]);
        $this->uow->expects(self::once())->method('getScheduledCollectionDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->tagGenerator->expects(static::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, false, false, ['entity1Tag']],
                [$entity2, false, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(static::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush(new PostFlushEventArgs($this->em));
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

        $this->uow->expects(self::once())->method('getScheduledEntityInsertions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityUpdates')->willReturn([
            $entity1
        ]);
        $this->uow->expects(self::once())->method('getScheduledCollectionDeletions')->willReturn([
            $coll1,
            $coll2,
            $coll3
        ]);
        $this->uow->expects(self::once())->method('getScheduledCollectionUpdates')->willReturn([]);

        $this->tagGenerator->expects(static::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, false, false, ['entity1Tag']],
                [$entity2, false, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(static::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush(new PostFlushEventArgs($this->em));
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

        $this->uow->expects(self::once())->method('getScheduledEntityInsertions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledEntityUpdates')->willReturn([
            $entity1
        ]);
        $this->uow->expects(self::once())->method('getScheduledCollectionDeletions')->willReturn([]);
        $this->uow->expects(self::once())->method('getScheduledCollectionUpdates')->willReturn([
            $coll1,
            $coll2,
            $coll3
        ]);

        $this->tagGenerator->expects(static::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, false, false, ['entity1Tag']],
                [$entity2, false, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(static::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush(new PostFlushEventArgs($this->em));
    }
}
