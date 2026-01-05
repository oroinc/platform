<?php

declare(strict_types=1);

namespace Oro\Bundle\CacheBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CacheBundle\EventListener\ResetOnEntityChangeListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ResetInterface;

final class ResetOnEntityChangeListenerTest extends TestCase
{
    private ResetInterface&MockObject $serviceToReset;
    private UnitOfWork&MockObject $unitOfWork;
    private OnFlushEventArgs&MockObject $eventArgs;
    private ResetOnEntityChangeListener $listener;
    private object $sampleEntity;

    #[\Override]
    protected function setUp(): void
    {
        $this->serviceToReset = $this->createMock(ResetInterface::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->eventArgs = $this->createMock(OnFlushEventArgs::class);
        $this->sampleEntity = new class () {
        };

        $this->listener = new ResetOnEntityChangeListener(
            $this->serviceToReset,
            [$this->sampleEntity::class]
        );

        $this->eventArgs->expects(self::any())
            ->method('getObjectManager')
            ->willReturn($entityManager);
        $entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
    }

    public function testOnFlushWithInsertedEntityTriggersReset(): void
    {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->sampleEntity]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->listener->onFlush($this->eventArgs);

        $this->serviceToReset->expects(self::once())
            ->method('reset');

        $this->listener->postFlush();
    }

    public function testOnFlushWithUpdatedEntityTriggersReset(): void
    {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$this->sampleEntity]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->listener->onFlush($this->eventArgs);

        $this->serviceToReset->expects(self::once())
            ->method('reset');

        $this->listener->postFlush();
    }

    public function testOnFlushWithDeletedEntityTriggersReset(): void
    {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$this->sampleEntity]);

        $this->listener->onFlush($this->eventArgs);

        $this->serviceToReset->expects(self::once())
            ->method('reset');

        $this->listener->postFlush();
    }

    public function testOnFlushWithoutTrackedEntityDoesNotTriggerReset(): void
    {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new \stdClass()]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->listener->onFlush($this->eventArgs);

        $this->serviceToReset->expects(self::never())
            ->method('reset');

        $this->listener->postFlush();
    }

    public function testOnClearResetsFlag(): void
    {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->sampleEntity]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn([]);

        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);

        $this->listener->onFlush($this->eventArgs);
        $this->listener->onClear();

        $this->serviceToReset->expects(self::never())
            ->method('reset');

        $this->listener->postFlush();
    }

    public function testPostFlushWithoutOnFlushDoesNothing(): void
    {
        $this->serviceToReset->expects(self::never())
            ->method('reset');

        $this->listener->postFlush();
    }
}
