<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Factory;

use Oro\Component\DraftSession\Event\EntityDraftCreatedEvent;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryChain;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityDraftFactoryChainTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testSupportsReturnsTrueWhenFactorySupportsTheClass(): void
    {
        $factory = $this->createMock(EntityDraftFactoryInterface::class);
        $factory->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);

        $chain = new EntityDraftFactoryChain([$factory], $this->eventDispatcher);

        self::assertTrue($chain->supports(EntityDraftAwareStub::class));
    }

    public function testSupportsReturnsFalseWhenNoFactorySupportsTheClass(): void
    {
        $factory = $this->createMock(EntityDraftFactoryInterface::class);
        $factory->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);

        $chain = new EntityDraftFactoryChain([$factory], $this->eventDispatcher);

        self::assertFalse($chain->supports(EntityDraftAwareStub::class));
    }

    public function testSupportsStopsIteratingOnFirstSupportingFactory(): void
    {
        $factory1 = $this->createMock(EntityDraftFactoryInterface::class);
        $factory1->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);

        $factory2 = $this->createMock(EntityDraftFactoryInterface::class);
        $factory2->expects(self::never())
            ->method('supports');

        $chain = new EntityDraftFactoryChain([$factory1, $factory2], $this->eventDispatcher);

        self::assertTrue($chain->supports(EntityDraftAwareStub::class));
    }

    public function testCreateDraftDelegatesToFirstSupportingFactory(): void
    {
        $source = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub();

        $factory = $this->createMock(EntityDraftFactoryInterface::class);
        $factory->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $factory->expects(self::once())
            ->method('createDraft')
            ->with($source, 'uuid-1234')
            ->willReturn($draft);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EntityDraftCreatedEvent::class));

        $chain = new EntityDraftFactoryChain([$factory], $this->eventDispatcher);

        $result = $chain->createDraft($source, 'uuid-1234');

        self::assertSame($draft, $result);
    }

    public function testCreateDraftSkipsUnsupportingFactoriesAndUsesFirstMatch(): void
    {
        $source = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub();

        $factory1 = $this->createMock(EntityDraftFactoryInterface::class);
        $factory1->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);
        $factory1->expects(self::never())
            ->method('createDraft');

        $factory2 = $this->createMock(EntityDraftFactoryInterface::class);
        $factory2->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);
        $factory2->expects(self::once())
            ->method('createDraft')
            ->with($source, 'uuid-5678')
            ->willReturn($draft);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EntityDraftCreatedEvent::class));

        $chain = new EntityDraftFactoryChain([$factory1, $factory2], $this->eventDispatcher);

        $result = $chain->createDraft($source, 'uuid-5678');

        self::assertSame($draft, $result);
    }

    public function testCreateDraftDispatchesEventWithSourceAndDraft(): void
    {
        $source = new EntityDraftAwareStub();
        $draft = new EntityDraftAwareStub();
        $dispatchedEvent = null;

        $factory = $this->createMock(EntityDraftFactoryInterface::class);
        $factory
            ->method('supports')
            ->willReturn(true);
        $factory
            ->method('createDraft')
            ->willReturn($draft);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (EntityDraftCreatedEvent $event) use (&$dispatchedEvent) {
                $dispatchedEvent = $event;

                return $event;
            });

        $chain = new EntityDraftFactoryChain([$factory], $this->eventDispatcher);
        $chain->createDraft($source, 'uuid-abcd');

        self::assertSame($source, $dispatchedEvent->getEntity());
        self::assertSame($draft, $dispatchedEvent->getDraft());
    }

    public function testCreateDraftThrowsLogicExceptionWhenNoFactorySupports(): void
    {
        $source = new EntityDraftAwareStub();

        $factory = $this->createMock(EntityDraftFactoryInterface::class);
        $factory->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);
        $factory->expects(self::never())
            ->method('createDraft');

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $chain = new EntityDraftFactoryChain([$factory], $this->eventDispatcher);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('No entity draft factory found for entity class "%s".', EntityDraftAwareStub::class)
        );

        $chain->createDraft($source, 'uuid-0000');
    }

    public function testCreateDraftThrowsWhenFactoryListIsEmpty(): void
    {
        $source = new EntityDraftAwareStub();

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $chain = new EntityDraftFactoryChain([], $this->eventDispatcher);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf('No entity draft factory found for entity class "%s".', EntityDraftAwareStub::class)
        );

        $chain->createDraft($source, 'uuid-0000');
    }
}
