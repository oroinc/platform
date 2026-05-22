<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\DraftSession\Event\EntityDraftPersistAfterEvent;
use Oro\Component\DraftSession\Event\EntityDraftPersistBeforeEvent;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Isolator\DoctrineListenersIsolator;
use Oro\Component\DraftSession\Isolator\DraftEntitiesEntityManagerIsolator;
use Oro\Component\DraftSession\Manager\EntityDraftPersister;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftSoftDeleteAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityDraftPersisterTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EntityDraftRepositoryInterface&MockObject $entityDraftRepository;

    private DraftSessionUuidProvider&MockObject $draftSessionUuidProvider;

    private EntityDraftFactoryInterface&MockObject $entityDraftFactory;

    private EntityDraftSynchronizerInterface&MockObject $entityDraftSynchronizer;

    private DoctrineListenersIsolator&MockObject $doctrineListenersIsolator;

    private DraftEntitiesEntityManagerIsolator&MockObject $entityManagerIsolator;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private EntityManagerInterface&MockObject $entityManager;

    private UnitOfWork&MockObject $unitOfWork;

    private EntityDraftPersister $persister;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $this->draftSessionUuidProvider = $this->createMock(DraftSessionUuidProvider::class);
        $this->entityDraftFactory = $this->createMock(EntityDraftFactoryInterface::class);
        $this->entityDraftSynchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $this->doctrineListenersIsolator = $this->createMock(DoctrineListenersIsolator::class);
        $this->entityManagerIsolator = $this->createMock(DraftEntitiesEntityManagerIsolator::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $doctrine
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->entityManager
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);

        $this->persister = new EntityDraftPersister(
            $doctrine,
            $this->entityDraftRepository,
            $this->draftSessionUuidProvider,
            $this->entityDraftFactory,
            $this->entityDraftSynchronizer,
            $this->doctrineListenersIsolator,
            $this->entityManagerIsolator,
            $this->eventDispatcher,
        );

        $this->setUpLoggerMock($this->persister);
    }

    public function testSaveToEntityDraftReturnsEntityWhenResolvedDraftSessionUuidIsNull(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->unitOfWork
            ->expects(self::never())
            ->method('isScheduledForDelete');

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->doctrineListenersIsolator
            ->expects(self::never())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('hasEntityDraft');

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');

        $this->entityDraftFactory
            ->expects(self::never())
            ->method('createDraft');

        $this->entityManagerIsolator
            ->expects(self::never())
            ->method('flushDraftEntities');

        $debugLogs = [];
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('debug')
            ->willReturnCallback(static function (string $message, array $context = []) use (&$debugLogs): void {
                $debugLogs[] = ['message' => $message, 'context' => $context];
            });

        $result = $this->persister->saveToEntityDraft($entity);

        self::assertSame($entity, $result);
    }

    public function testSaveToEntityDraftCreatesDraftWhenNoDraftExists(): void
    {
        $entity = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub(100);
        $draft->setDraftSessionUuid('provider-uuid');
        $draft->setDraftSource($entity);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('provider-uuid');

        $this->unitOfWork
            ->expects(self::once())
            ->method('isScheduledForDelete')
            ->with($entity)
            ->willReturn(false);

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(EntityDraftPersistBeforeEvent::class)],
                [self::isInstanceOf(EntityDraftPersistAfterEvent::class)]
            )
            ->willReturnArgument(0);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'provider-uuid')
            ->willReturn(null);

        $this->entityDraftFactory
            ->expects(self::once())
            ->method('createDraft')
            ->with($entity, 'provider-uuid')
            ->willReturn($draft);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($draft);

        $this->entityManagerIsolator
            ->expects(self::once())
            ->method('flushDraftEntities')
            ->with($this->entityManager);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('enableListeners');

        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('debug');

        $result = $this->persister->saveToEntityDraft($entity);

        self::assertSame($draft, $result);
    }

    public function testSaveToEntityDraftSynchronizesExistingDraftAndAppliesSoftDeleteFlag(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $draft = new EntityDraftSoftDeleteAwareStub(100);
        $draft->setDraftSessionUuid('explicit-uuid');
        $draft->setDraftSource($entity);

        $this->unitOfWork
            ->expects(self::once())
            ->method('isScheduledForDelete')
            ->with($entity)
            ->willReturn(true);

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(EntityDraftPersistBeforeEvent::class)],
                [self::isInstanceOf(EntityDraftPersistAfterEvent::class)]
            )
            ->willReturnArgument(0);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn($draft);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft')
            ->with($entity, $draft);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($draft);

        $this->entityManagerIsolator
            ->expects(self::once())
            ->method('flushDraftEntities')
            ->with($this->entityManager);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('enableListeners');

        $result = $this->persister->saveToEntityDraft($entity, 'explicit-uuid');

        self::assertSame($draft, $result);
        self::assertTrue($draft->isDraftDelete());
    }

    public function testSaveToEntityDraftPersistsDraftDirectlyWhenInputIsDraft(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $inputDraft = new EntityDraftAwareStub(100);
        $inputDraft->setDraftSessionUuid('explicit-uuid');
        $inputDraft->setDraftSource($entity);

        $this->unitOfWork
            ->expects(self::once())
            ->method('isScheduledForDelete')
            ->with($inputDraft)
            ->willReturn(false);

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('hasEntityDraft');

        $this->entityDraftFactory
            ->expects(self::never())
            ->method('createDraft');

        $this->entityDraftSynchronizer
            ->expects(self::never())
            ->method('synchronizeToDraft');

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(EntityDraftPersistBeforeEvent::class)],
                [self::isInstanceOf(EntityDraftPersistAfterEvent::class)]
            )
            ->willReturnArgument(0);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('disableListeners');

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($inputDraft);

        $this->entityManagerIsolator
            ->expects(self::once())
            ->method('flushDraftEntities')
            ->with($this->entityManager);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('enableListeners');

        $result = $this->persister->saveToEntityDraft($inputDraft, 'explicit-uuid');

        self::assertSame($inputDraft, $result);
    }

    public function testSaveToEntityDraftDoesNotApplySoftDeleteFlagWhenEntityIsNotScheduledForDelete(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $draft = new EntityDraftSoftDeleteAwareStub(100);
        $draft->setDraftSessionUuid('explicit-uuid');
        $draft->setDraftSource($entity);

        $this->unitOfWork
            ->expects(self::once())
            ->method('isScheduledForDelete')
            ->with($entity)
            ->willReturn(false);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn($draft);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeToDraft')
            ->with($entity, $draft);

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnArgument(0);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with($draft);

        $this->entityManagerIsolator
            ->expects(self::once())
            ->method('flushDraftEntities');

        $result = $this->persister->saveToEntityDraft($entity, 'explicit-uuid');

        self::assertSame($draft, $result);
        self::assertFalse($draft->isDraftDelete());
    }

    public function testSaveToEntityDraftEnablesListenersWhenExceptionOccurs(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->unitOfWork
            ->expects(self::once())
            ->method('isScheduledForDelete')
            ->with($entity)
            ->willReturn(false);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn(null);

        $this->entityDraftFactory
            ->expects(self::once())
            ->method('createDraft')
            ->with($entity, 'explicit-uuid')
            ->willThrowException(new \RuntimeException('Test error'));

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('enableListeners');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test error');

        $this->persister->saveToEntityDraft($entity, 'explicit-uuid');
    }
}
