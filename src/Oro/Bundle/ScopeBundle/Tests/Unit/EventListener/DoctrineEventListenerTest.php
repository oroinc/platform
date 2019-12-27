<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub as Entity;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\EventListener\DoctrineEventListener;
use Oro\Bundle\ScopeBundle\Manager\ScopeCollection;

class DoctrineEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeCollection|\PHPUnit\Framework\MockObject\MockObject */
    private $scheduledForInsertScopes;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeCache;

    /** @var DoctrineEventListener */
    private $listener;

    protected function setUp()
    {
        $this->scheduledForInsertScopes = $this->createMock(ScopeCollection::class);
        $this->scopeCache = $this->createMock(CacheProvider::class);

        $this->listener = new DoctrineEventListener($this->scheduledForInsertScopes, $this->scopeCache);
    }

    public function testPreFlushForEmptyScheduledForInsertScopes()
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $this->scheduledForInsertScopes->expects($this->never())
            ->method('getAll');
        $this->scheduledForInsertScopes->expects($this->never())
            ->method('clear');
        $em->expects($this->never())
            ->method('persist');

        $this->listener->preFlush(new PreFlushEventArgs($em));
    }

    public function testPreFlushForNotEmptyScheduledForInsertScopes()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $scope1 = new Scope();
        $scope2 = new Scope();

        $this->scheduledForInsertScopes->expects($this->once())
            ->method('isEmpty')
            ->willReturn(false);
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('getAll')
            ->willReturn([$scope1, $scope2]);
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('clear');
        $em->expects($this->exactly(2))
            ->method('persist');
        $em->expects($this->at(0))
            ->method('persist')
            ->with($this->identicalTo($scope1));
        $em->expects($this->at(1))
            ->method('persist')
            ->with($this->identicalTo($scope2));

        $this->listener->preFlush(new PreFlushEventArgs($em));
    }

    public function testOnClear()
    {
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('clear');

        $this->listener->onClear();
    }

    public function testFlushWhenNoChangesRequireResetScopeCache()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $scopeMetadata = $this->createMock(ClassMetadata::class);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Entity()]);
        $uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new Entity()]);
        $uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([new Entity()]);

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Scope::class)
            ->willReturn($scopeMetadata);
        $scopeMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'association1' => [
                    'targetEntity' => \stdClass::class
                ]
            ]);

        $this->scopeCache->expects($this->never())
            ->method('deleteAll');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush();
    }

    public function testNeedToResetScopeCacheFlag()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Scope()]);

        $this->scopeCache->expects($this->once())
            ->method('deleteAll');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush();
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeEntityCreatedThatRequireResetScopeCache()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Scope()]);
        $uow->expects($this->never())
            ->method('getScheduledEntityUpdates');
        $uow->expects($this->never())
            ->method('getScheduledEntityDeletions');

        $em->expects($this->never())
            ->method('getClassMetadata')
            ->with(Scope::class);

        $this->scopeCache->expects($this->once())
            ->method('deleteAll');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeEntityUpdatedThatRequireResetScopeCache()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Entity()]);
        $uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new Scope()]);
        $uow->expects($this->never())
            ->method('getScheduledEntityDeletions');

        $em->expects($this->never())
            ->method('getClassMetadata')
            ->with(Scope::class);

        $this->scopeCache->expects($this->once())
            ->method('deleteAll');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeEntityDeletedThatRequireResetScopeCache()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $scopeMetadata = $this->createMock(ClassMetadata::class);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Entity()]);
        $uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new Entity()]);
        $uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([new Scope()]);

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Scope::class)
            ->willReturn($scopeMetadata);
        $scopeMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'association1' => [
                    'targetEntity' => \stdClass::class
                ]
            ]);

        $this->scopeCache->expects($this->once())
            ->method('deleteAll');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeAssociationTargetEntityDeletedThatRequireResetScopeCache()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $scopeMetadata = $this->createMock(ClassMetadata::class);

        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Entity()]);
        $uow->expects($this->once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new Entity()]);
        $uow->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([new Entity()]);

        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Scope::class)
            ->willReturn($scopeMetadata);
        $scopeMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->willReturn([
                'association1' => [
                    'targetEntity' => Entity::class
                ]
            ]);

        $this->scopeCache->expects($this->once())
            ->method('deleteAll');

        $this->listener->onFlush(new OnFlushEventArgs($em));
        $this->listener->postFlush();
    }
}
