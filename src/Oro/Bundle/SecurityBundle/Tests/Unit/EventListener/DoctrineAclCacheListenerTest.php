<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Oro\Bundle\SecurityBundle\EventListener\DoctrineAclCacheListener;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\UserBundle\Entity\Role;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineAclCacheListenerTest extends TestCase
{
    private DoctrineAclCacheProvider&MockObject $queryCacheProvider;
    private OwnerTreeProviderInterface&MockObject $ownerTreeProvider;
    private DoctrineAclCacheListener $listener;

    protected function setUp(): void
    {
        $this->queryCacheProvider = $this->createMock(DoctrineAclCacheProvider::class);
        $this->ownerTreeProvider = $this->createMock(OwnerTreeProviderInterface::class);

        $this->listener = new DoctrineAclCacheListener(
            $this->queryCacheProvider,
            $this->ownerTreeProvider
        );
        $this->listener->addEntityShouldBeProcessedByUpdate(Organization::class, ['enabled' => true]);
    }

    public function testOwnerTreeShouldNotBeTriggeredForUpdateNonBusinessUnitEntity(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $entity = new Role();

        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity]);

        $uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $this->ownerTreeProvider->expects(self::never())
            ->method('getTree');

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    public function testOwnerTreeShouldBeTriggeredForUpdateBusinessUnitEntity(): void
    {
        $ownerTree = $this->createMock(OwnerTree::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $entity = new BusinessUnit();

        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$entity]);

        $uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $uow->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($entity)
            ->willReturn(['owner' => [null, new BusinessUnit()]]);

        $this->ownerTreeProvider->expects(self::once())
            ->method('getTree')
            ->willReturn($ownerTree);

        $ownerTree->expects(self::once())
            ->method('getUsersAssignedToBusinessUnits')
            ->willReturn([]);

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    public function testOwnerTreeShouldNotBeTriggeredForDeleteNonBusinessUnitEntity(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $entity = new Role();

        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$entity]);

        $uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $this->ownerTreeProvider->expects(self::never())
            ->method('getTree');

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }

    public function testOwnerTreeShouldBeTriggeredForDeleteBusinessUnitEntity(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $ownerTree = $this->createMock(OwnerTree::class);

        $entity = new BusinessUnit();

        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$entity]);

        $uow->expects(self::once())
            ->method('getScheduledCollectionUpdates')
            ->willReturn([]);

        $this->ownerTreeProvider->expects(self::once())
            ->method('getTree')
            ->willReturn($ownerTree);

        $ownerTree->expects(self::once())
            ->method('getUsersAssignedToBusinessUnits')
            ->willReturn([]);

        $ownerTree->expects(self::once())
            ->method('getSubordinateBusinessUnitIds')
            ->willReturn([]);

        $this->listener->onFlush(new OnFlushEventArgs($em));
    }
}
