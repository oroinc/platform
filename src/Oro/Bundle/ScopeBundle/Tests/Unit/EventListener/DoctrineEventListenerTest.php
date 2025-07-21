<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub as Entity;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\EventListener\DoctrineEventListener;
use Oro\Bundle\ScopeBundle\Manager\ScopeCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class DoctrineEventListenerTest extends TestCase
{
    private ScopeCollection&MockObject $scheduledForInsertScopes;
    private CacheItemPoolInterface&MockObject $scopeCache;
    private EntityManagerInterface&MockObject $em;
    private DoctrineEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->scheduledForInsertScopes = $this->createMock(ScopeCollection::class);
        $this->scopeCache = $this->createMock(CacheItemPoolInterface::class);

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->listener = new DoctrineEventListener($this->scheduledForInsertScopes, $this->scopeCache);
    }

    public function testPreFlushForEmptyScheduledForInsertScopes(): void
    {
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);
        $this->scheduledForInsertScopes->expects($this->never())
            ->method('getAll');
        $this->scheduledForInsertScopes->expects($this->never())
            ->method('clear');
        $this->em->expects($this->never())
            ->method('persist');

        $this->listener->preFlush(new PreFlushEventArgs($this->em));
    }

    public function testPreFlushForNotEmptyScheduledForInsertScopes(): void
    {
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
        $this->em->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$scope1],
                [$scope2]
            );

        $this->listener->preFlush(new PreFlushEventArgs($this->em));
    }

    public function testOnClear(): void
    {
        $this->scheduledForInsertScopes->expects($this->once())
            ->method('clear');

        $this->listener->onClear();
    }

    public function testFlushWhenNoChangesRequireResetScopeCache(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $scopeMetadata = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->once())
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

        $this->em->expects($this->once())
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
            ->method('clear');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testNeedToResetScopeCacheFlag(): void
    {
        $uow = $this->createMock(UnitOfWork::class);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Scope()]);

        $this->scopeCache->expects($this->once())
            ->method('clear');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeEntityCreatedThatRequireResetScopeCache(): void
    {
        $uow = $this->createMock(UnitOfWork::class);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new Scope()]);
        $uow->expects($this->never())
            ->method('getScheduledEntityUpdates');
        $uow->expects($this->never())
            ->method('getScheduledEntityDeletions');

        $this->em->expects($this->never())
            ->method('getClassMetadata')
            ->with(Scope::class);

        $this->scopeCache->expects($this->once())
            ->method('clear');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeEntityUpdatedThatRequireResetScopeCache(): void
    {
        $uow = $this->createMock(UnitOfWork::class);

        $this->em->expects($this->once())
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

        $this->em->expects($this->never())
            ->method('getClassMetadata')
            ->with(Scope::class);

        $this->scopeCache->expects($this->once())
            ->method('clear');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeEntityDeletedThatRequireResetScopeCache(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $scopeMetadata = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->once())
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

        $this->em->expects($this->once())
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
            ->method('clear');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testFlushWhenScopeAssociationTargetEntityDeletedThatRequireResetScopeCache(): void
    {
        $uow = $this->createMock(UnitOfWork::class);
        $scopeMetadata = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->once())
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

        $this->em->expects($this->once())
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
            ->method('clear');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }
}
