<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\DraftSession\Event\EntityDraftDeleteBeforeEvent;
use Oro\Component\DraftSession\Isolator\DoctrineListenersIsolator;
use Oro\Component\DraftSession\Isolator\DraftEntitiesEntityManagerIsolator;
use Oro\Component\DraftSession\Manager\EntityDraftRemover;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityDraftRemoverTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EntityDraftRepositoryInterface&MockObject $entityDraftRepository;

    private DraftSessionUuidProvider&MockObject $draftSessionUuidProvider;

    private DoctrineListenersIsolator&MockObject $doctrineListenersIsolator;

    private DraftEntitiesEntityManagerIsolator&MockObject $entityManagerIsolator;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private EntityManagerInterface&MockObject $entityManager;

    private EntityDraftRemover $remover;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $this->draftSessionUuidProvider = $this->createMock(DraftSessionUuidProvider::class);
        $this->doctrineListenersIsolator = $this->createMock(DoctrineListenersIsolator::class);
        $this->entityManagerIsolator = $this->createMock(DraftEntitiesEntityManagerIsolator::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $doctrine
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);

        $this->remover = new EntityDraftRemover(
            $doctrine,
            $this->entityDraftRepository,
            $this->draftSessionUuidProvider,
            $this->doctrineListenersIsolator,
            $this->entityManagerIsolator,
            $this->eventDispatcher,
        );

        $this->setUpLoggerMock($this->remover);
    }

    public function testDeleteEntityDraftDoesNothingWhenResolvedSessionUuidIsNull(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->doctrineListenersIsolator
            ->expects(self::never())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');

        $this->entityManager
            ->expects(self::never())
            ->method('remove');

        $this->remover->deleteEntityDraft($entity);
    }

    public function testDeleteEntityDraftRemovesDraftAndDispatchesEvent(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('provider-uuid');

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('provider-uuid');

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'provider-uuid')
            ->willReturn($entityDraft);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($entityDraft);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (object $event) use ($entityDraft): bool {
                return $event instanceof EntityDraftDeleteBeforeEvent && $event->getDraft() === $entityDraft;
            }))
            ->willReturnArgument(0);

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

        $this->remover->deleteEntityDraft($entity);
    }

    public function testDeleteEntityDraftDoesNothingWhenDraftDoesNotExist(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn(null);

        $this->entityManager
            ->expects(self::never())
            ->method('remove');

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->entityManagerIsolator
            ->expects(self::never())
            ->method('flushDraftEntities');

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('enableListeners');

        $loggedMessages = [];
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('debug')
            ->willReturnCallback(static function (string $message) use (&$loggedMessages): void {
                $loggedMessages[] = $message;
            });

        $this->remover->deleteEntityDraft($entity, 'explicit-uuid');

        self::assertContains('Draft was not found for entity {entity_class}.', $loggedMessages);
    }

    public function testDeleteEntityDraftEnablesListenersWhenFlushThrowsException(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('explicit-uuid');

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('disableListeners');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn($entityDraft);

        $this->entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($entityDraft);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EntityDraftDeleteBeforeEvent::class))
            ->willReturnArgument(0);

        $this->entityManagerIsolator
            ->expects(self::once())
            ->method('flushDraftEntities')
            ->willThrowException(new \RuntimeException('Flush error'));

        $this->doctrineListenersIsolator
            ->expects(self::once())
            ->method('enableListeners');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Flush error');

        $this->remover->deleteEntityDraft($entity, 'explicit-uuid');
    }
}
