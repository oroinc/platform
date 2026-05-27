<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Synchronizer;

use Oro\Component\DraftSession\Event\EntityFromDraftSyncBeforeEvent;
use Oro\Component\DraftSession\Event\EntityFromDraftSyncEvent;
use Oro\Component\DraftSession\Event\EntityToDraftSyncBeforeEvent;
use Oro\Component\DraftSession\Event\EntityToDraftSyncEvent;
use Oro\Component\DraftSession\Exception\DraftSessionLogicException;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerChain;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityDraftSynchronizerChainTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testSupportsReturnsTrueWhenSynchronizerSupportsTheClass(): void
    {
        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);

        self::assertTrue($chain->supports(EntityDraftAwareStub::class));
    }

    public function testSupportsReturnsFalseWhenNoSynchronizerSupportsTheClass(): void
    {
        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);

        self::assertFalse($chain->supports(EntityDraftAwareStub::class));
    }

    public function testSupportsStopsIteratingOnFirstSupportingSynchronizer(): void
    {
        $synchronizer1 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer1->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);

        $synchronizer2 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer2->expects(self::never())
            ->method('supports');

        $chain = new EntityDraftSynchronizerChain([$synchronizer1, $synchronizer2], $this->eventDispatcher);

        self::assertTrue($chain->supports(EntityDraftAwareStub::class));
    }

    public function testSynchronizeFromDraftDelegatesToSupportingSynchronizer(): void
    {
        $draft = new EntityDraftAwareStub();
        $entity = new EntityDraftAwareStub();

        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $synchronizer->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($draft, $entity);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(EntityFromDraftSyncBeforeEvent::class)],
                [self::isInstanceOf(EntityFromDraftSyncEvent::class)]
            );

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);
        $chain->synchronizeFromDraft($draft, $entity);
    }

    public function testSynchronizeFromDraftCallsAllSupportingSynchronizers(): void
    {
        $draft = new EntityDraftAwareStub();
        $entity = new EntityDraftAwareStub();

        $synchronizer1 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer1->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $synchronizer1->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($draft, $entity);

        $synchronizer2 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer2->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $synchronizer2->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($draft, $entity);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(EntityFromDraftSyncBeforeEvent::class)],
                [self::isInstanceOf(EntityFromDraftSyncEvent::class)]
            );

        $chain = new EntityDraftSynchronizerChain([$synchronizer1, $synchronizer2], $this->eventDispatcher);
        $chain->synchronizeFromDraft($draft, $entity);
    }

    public function testSynchronizeFromDraftSkipsUnsupportingSynchronizers(): void
    {
        $draft = new EntityDraftAwareStub();
        $entity = new EntityDraftAwareStub();

        $synchronizer1 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer1->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);
        $synchronizer1->expects(self::never())
            ->method('synchronizeFromDraft');

        $synchronizer2 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer2->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $synchronizer2->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($draft, $entity);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch');

        $chain = new EntityDraftSynchronizerChain([$synchronizer1, $synchronizer2], $this->eventDispatcher);
        $chain->synchronizeFromDraft($draft, $entity);
    }

    public function testSynchronizeFromDraftDispatchesEventWithDraftAndEntity(): void
    {
        $draft = new EntityDraftAwareStub();
        $entity = new EntityDraftAwareStub();
        $dispatchedEvents = [];

        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer->method('supports')->willReturn(true);
        $synchronizer->method('synchronizeFromDraft');

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatchedEvents): object {
                $dispatchedEvents[] = $event;

                return $event;
            });

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);
        $chain->synchronizeFromDraft($draft, $entity);

        self::assertCount(2, $dispatchedEvents);
        self::assertInstanceOf(EntityFromDraftSyncEvent::class, $dispatchedEvents[1]);
        self::assertSame($draft, $dispatchedEvents[1]->getSource());
        self::assertSame($entity, $dispatchedEvents[1]->getTarget());
    }

    public function testSynchronizeFromDraftThrowsLogicExceptionWhenNoSynchronizerSupports(): void
    {
        $draft = new EntityDraftAwareStub();
        $entity = new EntityDraftAwareStub();

        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);
        $synchronizer->expects(self::never())
            ->method('synchronizeFromDraft');

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage(
            sprintf('No entity draft synchronizer found for entity class "%s".', EntityDraftAwareStub::class)
        );

        $chain->synchronizeFromDraft($draft, $entity);
    }

    public function testSynchronizeToDraftDelegatesToSupportingSynchronizer(): void
    {
        $entity = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub();

        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $synchronizer->expects(self::once())
            ->method('synchronizeToDraft')
            ->with($entity, $draft);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(EntityToDraftSyncBeforeEvent::class)],
                [self::isInstanceOf(EntityToDraftSyncEvent::class)]
            );

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);
        $chain->synchronizeToDraft($entity, $draft);
    }

    public function testSynchronizeToDraftCallsAllSupportingSynchronizers(): void
    {
        $entity = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub();

        $synchronizer1 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer1->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $synchronizer1->expects(self::once())
            ->method('synchronizeToDraft')
            ->with($entity, $draft);

        $synchronizer2 = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer2->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $synchronizer2->expects(self::once())
            ->method('synchronizeToDraft')
            ->with($entity, $draft);

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(EntityToDraftSyncBeforeEvent::class)],
                [self::isInstanceOf(EntityToDraftSyncEvent::class)]
            );

        $chain = new EntityDraftSynchronizerChain([$synchronizer1, $synchronizer2], $this->eventDispatcher);
        $chain->synchronizeToDraft($entity, $draft);
    }

    public function testSynchronizeToDraftDispatchesEventWithEntityAndDraft(): void
    {
        $entity = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub();
        $dispatchedEvents = [];

        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer
            ->method('supports')
            ->willReturn(true);
        $synchronizer
            ->method('synchronizeToDraft');

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatchedEvents): object {
                $dispatchedEvents[] = $event;

                return $event;
            });

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);
        $chain->synchronizeToDraft($entity, $draft);

        self::assertCount(2, $dispatchedEvents);
        self::assertInstanceOf(EntityToDraftSyncBeforeEvent::class, $dispatchedEvents[0]);
        self::assertSame($entity, $dispatchedEvents[0]->getSource());
        self::assertSame($draft, $dispatchedEvents[0]->getTarget());
        self::assertInstanceOf(EntityToDraftSyncEvent::class, $dispatchedEvents[1]);
        self::assertSame($entity, $dispatchedEvents[1]->getSource());
        self::assertSame($draft, $dispatchedEvents[1]->getTarget());
    }

    public function testSynchronizeToDraftThrowsLogicExceptionWhenNoSynchronizerSupports(): void
    {
        $entity = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub();

        $synchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);
        $synchronizer->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);
        $synchronizer->expects(self::never())
            ->method('synchronizeToDraft');

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $chain = new EntityDraftSynchronizerChain([$synchronizer], $this->eventDispatcher);

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage(
            sprintf('No entity draft synchronizer found for entity class "%s".', EntityDraftAwareStub::class)
        );

        $chain->synchronizeToDraft($entity, $draft);
    }
}
