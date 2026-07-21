<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorInterface;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorRegistry;
use PHPUnit\Framework\TestCase;

final class NotifiableQueueIteratorRegistryTest extends TestCase
{
    public function testNotifyMessageReceivedBroadcastsToAllRegisteredIterators(): void
    {
        $notifiableQueueIterator1 = $this->createMock(NotifiableQueueIteratorInterface::class);
        $notifiableQueueIterator1->expects(self::once())
            ->method('notifyMessageReceived');

        $notifiableQueueIterator2 = $this->createMock(NotifiableQueueIteratorInterface::class);
        $notifiableQueueIterator2->expects(self::once())
            ->method('notifyMessageReceived');

        $notifiableQueueIteratorRegistry = new NotifiableQueueIteratorRegistry();
        $notifiableQueueIteratorRegistry->addQueueIterator($notifiableQueueIterator1);
        $notifiableQueueIteratorRegistry->addQueueIterator($notifiableQueueIterator2);

        $notifiableQueueIteratorRegistry->notifyMessageReceived();
    }

    public function testNotifyIdleBroadcastsToAllRegisteredIterators(): void
    {
        $notifiableQueueIterator1 = $this->createMock(NotifiableQueueIteratorInterface::class);
        $notifiableQueueIterator1->expects(self::once())
            ->method('notifyIdle');

        $notifiableQueueIterator2 = $this->createMock(NotifiableQueueIteratorInterface::class);
        $notifiableQueueIterator2->expects(self::once())
            ->method('notifyIdle');

        $notifiableQueueIteratorRegistry = new NotifiableQueueIteratorRegistry();
        $notifiableQueueIteratorRegistry->addQueueIterator($notifiableQueueIterator1);
        $notifiableQueueIteratorRegistry->addQueueIterator($notifiableQueueIterator2);

        $notifiableQueueIteratorRegistry->notifyIdle();
    }

    public function testClearRemovesAllIteratorsSoNotificationsAreNoLongerForwarded(): void
    {
        $iterator = $this->createMock(NotifiableQueueIteratorInterface::class);
        $iterator->expects(self::never())
            ->method('notifyMessageReceived');
        $iterator->expects(self::never())
            ->method('notifyIdle');

        $notifiableQueueIteratorRegistry = new NotifiableQueueIteratorRegistry();
        $notifiableQueueIteratorRegistry->addQueueIterator($iterator);

        $notifiableQueueIteratorRegistry->clear();

        $notifiableQueueIteratorRegistry->notifyMessageReceived();
        $notifiableQueueIteratorRegistry->notifyIdle();
    }

    public function testNotifyMessageReceivedOnEmptyRegistryDoesNotThrow(): void
    {
        $notifiableQueueIteratorRegistry = new NotifiableQueueIteratorRegistry();

        $notifiableQueueIteratorRegistry->notifyMessageReceived();

        // No exception means the test passes
        self::assertTrue(true);
    }

    public function testNotifyIdleOnEmptyRegistryDoesNotThrow(): void
    {
        $notifiableQueueIteratorRegistry = new NotifiableQueueIteratorRegistry();

        $notifiableQueueIteratorRegistry->notifyIdle();

        // No exception means the test passes
        self::assertTrue(true);
    }
}
