<?php

declare(strict_types=1);

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\QueueIterator;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableConsumptionExtension;
use Oro\Component\MessageQueue\Consumption\QueueIterator\NotifiableQueueIteratorRegistryInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NotifiableConsumptionExtensionTest extends TestCase
{
    private NotifiableQueueIteratorRegistryInterface&MockObject $queueIteratorRegistry;
    private NotifiableConsumptionExtension $notifiableConsumptionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->queueIteratorRegistry = $this->createMock(NotifiableQueueIteratorRegistryInterface::class);
        $this->notifiableConsumptionExtension = new NotifiableConsumptionExtension($this->queueIteratorRegistry);
    }

    public function testOnPostReceivedDelegatesToRegistryNotifyMessageReceived(): void
    {
        $this->queueIteratorRegistry->expects(self::once())
            ->method('notifyMessageReceived');

        $context = new Context($this->createMock(SessionInterface::class));

        $this->notifiableConsumptionExtension->onPostReceived($context);
    }

    public function testOnIdleDelegatesToRegistryNotifyIdle(): void
    {
        $this->queueIteratorRegistry->expects(self::once())
            ->method('notifyIdle');

        $context = new Context($this->createMock(SessionInterface::class));

        $this->notifiableConsumptionExtension->onIdle($context);
    }

    public function testOnInterruptedDelegatesToRegistryClear(): void
    {
        $this->queueIteratorRegistry->expects(self::once())
            ->method('clear');

        $context = new Context($this->createMock(SessionInterface::class));

        $this->notifiableConsumptionExtension->onInterrupted($context);
    }
}
