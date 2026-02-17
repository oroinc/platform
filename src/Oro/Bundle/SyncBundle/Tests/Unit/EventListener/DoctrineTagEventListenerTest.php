<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SyncBundle\Content\DataUpdateTopicSender;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;
use Oro\Bundle\SyncBundle\EventListener\DoctrineTagEventListener;
use Oro\Bundle\TestFrameworkBundle\Entity\TestDepartment;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineTagEventListenerTest extends TestCase
{
    private TagGeneratorInterface&MockObject $tagGenerator;
    private EntityManagerInterface&MockObject $em;
    private UnitOfWork&MockObject $uow;
    private DataUpdateTopicSender&MockObject $dataUpdateTopicSender;
    private DoctrineTagEventListener $eventListener;

    #[\Override]
    protected function setUp(): void
    {
        $this->tagGenerator = $this->createMock(TagGeneratorInterface::class);
        $this->dataUpdateTopicSender = $this->createMock(DataUpdateTopicSender::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $applicationState = $this->createMock(ApplicationState::class);
        $applicationState->expects(self::any())
            ->method('isInstalled')
            ->willReturn(true);

        $this->em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $container = TestContainerBuilder::create()
            ->add(TagGeneratorInterface::class, $this->tagGenerator)
            ->add(DataUpdateTopicSender::class, $this->dataUpdateTopicSender)
            ->getContainer($this);

        $this->eventListener = new DoctrineTagEventListener($container, $applicationState);
    }

    private function createPersistentCollection(object $owner): PersistentCollection
    {
        $coll = new PersistentCollection(
            $this->em,
            $this->createMock(ClassMetadata::class),
            new ArrayCollection()
        );
        $coll->setOwner($owner, ['inversedBy' => null, 'mappedBy' => 'test']);

        return $coll;
    }

    public function testOnFlushForDisabledListener(): void
    {
        $this->uow->expects(self::never())
            ->method('getScheduledEntityInsertions');
        $this->uow->expects(self::never())
            ->method('getScheduledEntityDeletions');
        $this->uow->expects(self::never())
            ->method('getScheduledEntityUpdates');
        $this->uow->expects(self::never())
            ->method('getScheduledCollectionDeletions');
        $this->uow->expects(self::never())
            ->method('getScheduledCollectionUpdates');

        $this->dataUpdateTopicSender->expects(self::never())
            ->method('send');
        $this->tagGenerator->expects(self::never())
            ->method('generate');

        $this->eventListener->setEnabled(false);
        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));
        $this->eventListener->postFlush();
    }

    public function testOnFlushForScheduledEntityInsertions(): void
    {
        $entity1 = new TestDepartment();
        $entity2 = new TestEmployee();
        $entity3 = new TestProduct();

        $this->eventListener->markSkipped(TestProduct::class);

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

        $this->tagGenerator->expects(self::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, true, false, ['entity1Tag']],
                [$entity2, true, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(self::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush();
    }

    public function testOnFlushForScheduledEntityDeletions(): void
    {
        $entity1 = new TestDepartment();
        $entity2 = new TestEmployee();
        $entity3 = new TestProduct();

        $this->eventListener->markSkipped(TestProduct::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([ $entity1, $entity2, $entity3]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $this->tagGenerator->expects(self::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, true, false, ['entity1Tag']],
                [$entity2, true, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(self::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush();
    }

    public function testOnFlushForScheduledEntityUpdates(): void
    {
        $entity1 = new TestDepartment();
        $entity2 = new TestEmployee();
        $entity3 = new TestProduct();

        $this->eventListener->markSkipped(TestProduct::class);

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

        $this->tagGenerator->expects(self::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, false, false, ['entity1Tag']],
                [$entity2, false, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(self::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush();
    }

    public function testOnFlushForScheduledCollectionDeletions(): void
    {
        $entity1 = new TestDepartment();
        $coll1 = $this->createPersistentCollection($entity1);
        $entity2 = new TestEmployee();
        $coll2 = $this->createPersistentCollection($entity2);
        $entity3 = new TestProduct();
        $coll3 = $this->createPersistentCollection($entity3);

        $this->eventListener->markSkipped(TestProduct::class);

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

        $this->tagGenerator->expects(self::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, false, false, ['entity1Tag']],
                [$entity2, false, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(self::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush();
    }

    public function testOnFlushForScheduledCollectionUpdates(): void
    {
        $entity1 = new TestDepartment();
        $coll1 = $this->createPersistentCollection($entity1);
        $entity2 = new TestEmployee();
        $coll2 = $this->createPersistentCollection($entity2);
        $entity3 = new TestProduct();
        $coll3 = $this->createPersistentCollection($entity3);

        $this->eventListener->markSkipped(TestProduct::class);

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

        $this->tagGenerator->expects(self::exactly(2))
            ->method('generate')
            ->willReturnMap([
                [$entity1, false, false, ['entity1Tag']],
                [$entity2, false, false, ['entity2Tag']],
            ]);

        $this->eventListener->onFlush(new OnFlushEventArgs($this->em));

        $this->dataUpdateTopicSender->expects(self::once())
            ->method('send')
            ->with(['entity1Tag', 'entity2Tag']);

        $this->eventListener->postFlush();
    }
}
