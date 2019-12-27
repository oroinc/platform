<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub as Entity;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\EventListener\DoctrineEventListener;
use Oro\Bundle\ScopeBundle\Manager\ScopeEntityStorage;

class DoctrineEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeEntityStorage|\PHPUnit\Framework\MockObject\MockObject */
    private $entityStorage;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeCache;

    /** @var DoctrineEventListener */
    private $listener;

    protected function setUp()
    {
        $this->entityStorage = $this->createMock(ScopeEntityStorage::class);
        $this->scopeCache = $this->createMock(CacheProvider::class);

        $this->listener = new DoctrineEventListener($this->entityStorage, $this->scopeCache);
    }

    public function testPreFlush()
    {
        $this->entityStorage->expects($this->once())
            ->method('persistScheduledForInsert');
        $this->entityStorage->expects($this->once())
            ->method('clear');

        $this->listener->preFlush();
    }

    public function testOnClear()
    {
        $this->entityStorage->expects($this->once())
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
