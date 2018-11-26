<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\NotificationBundle\EventListener\InvalidateCacheListener;
use Oro\Bundle\NotificationBundle\Provider\NotificationManager;

class InvalidateCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|NotificationManager */
    private $notificationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UnitOfWork */
    private $uow;

    /** @var InvalidateCacheListener */
    private $listener;

    protected function setUp()
    {
        $this->notificationManager = $this->createMock(NotificationManager::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $this->listener = new InvalidateCacheListener($this->notificationManager);
    }

    public function testWhenNoChangesAffectsRulesCache()
    {
        $emailNotification = $this->createMock(EmailNotification::class);
        $event = $this->createMock(Event::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new \stdClass()]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([new \stdClass()]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([new \stdClass(), $emailNotification, $event]);
        $this->uow->expects(self::exactly(2))
            ->method('getEntityChangeSet')
            ->willReturnMap([
                [$emailNotification, ['template' => []]],
                [$event, ['description' => []]]
            ]);

        $this->notificationManager->expects(self::never())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testShouldNotClearCacheTwiceInCaseNestedOnFlushEvent()
    {
        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->createMock(EmailNotification::class)]);
        $this->uow->expects(self::never())
            ->method('getScheduledEntityDeletions');
        $this->uow->expects(self::never())
            ->method('getScheduledEntityUpdates');
        $this->uow->expects(self::never())
            ->method('getEntityChangeSet');

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));

        // emulate nested onFlush event
        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();

        $this->listener->postFlush();
    }

    public function testWhenHasInsertedEmailNotification()
    {
        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->createMock(EmailNotification::class)]);
        $this->uow->expects(self::never())
            ->method('getScheduledEntityDeletions');
        $this->uow->expects(self::never())
            ->method('getScheduledEntityUpdates');
        $this->uow->expects(self::never())
            ->method('getEntityChangeSet');

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testWhenHasInsertedEvent()
    {
        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->createMock(Event::class)]);
        $this->uow->expects(self::never())
            ->method('getScheduledEntityDeletions');
        $this->uow->expects(self::never())
            ->method('getScheduledEntityUpdates');
        $this->uow->expects(self::never())
            ->method('getEntityChangeSet');

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testWhenHasDeletedEmailNotification()
    {
        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$this->createMock(EmailNotification::class)]);
        $this->uow->expects(self::never())
            ->method('getScheduledEntityUpdates');
        $this->uow->expects(self::never())
            ->method('getEntityChangeSet');

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testWhenHasDeletedEmailEvent()
    {
        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$this->createMock(Event::class)]);
        $this->uow->expects(self::never())
            ->method('getScheduledEntityUpdates');
        $this->uow->expects(self::never())
            ->method('getEntityChangeSet');

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testWhenHasUpdatedEntityNameFieldForEmailNotification()
    {
        $emailNotification = $this->createMock(EmailNotification::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$emailNotification]);
        $this->uow->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($emailNotification)
            ->willReturn(['entityName' => []]);

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testWhenHasUpdatedEventFieldForEmailNotification()
    {
        $emailNotification = $this->createMock(EmailNotification::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$emailNotification]);
        $this->uow->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($emailNotification)
            ->willReturn(['event' => []]);

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }

    public function testWhenHasUpdatedNameFieldForEvent()
    {
        $event = $this->createMock(Event::class);

        $this->uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([]);
        $this->uow->expects(self::once())
            ->method('getScheduledEntityUpdates')
            ->willReturn([$event]);
        $this->uow->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($event)
            ->willReturn(['name' => []]);

        $this->notificationManager->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush();
    }
}
