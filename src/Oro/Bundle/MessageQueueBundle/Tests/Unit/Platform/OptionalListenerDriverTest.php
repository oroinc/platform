<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Platform;

use Oro\Bundle\MessageQueueBundle\Platform\OptionalListenerDriver;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Provider\Console\OptionalListenersGlobalOptionsProvider;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use PHPUnit\Framework\TestCase;

class OptionalListenerDriverTest extends TestCase
{
    public function testGetConfig(): void
    {
        $wrappedDriver = $this->createMock(DriverInterface::class);
        $wrappedDriver->expects($this->once())
            ->method('getConfig');
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);

        $driver = new OptionalListenerDriver($wrappedDriver, $optionalListenerManager);
        $driver->getConfig();
    }

    public function testCreateQueue(): void
    {
        $wrappedDriver = $this->createMock(DriverInterface::class);
        $wrappedDriver->expects($this->once())
            ->method('createQueue')->with('name');
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);

        $driver = new OptionalListenerDriver($wrappedDriver, $optionalListenerManager);
        $driver->createQueue('name');
    }

    public function testCreateTransportMessage(): void
    {
        $wrappedDriver = $this->createMock(DriverInterface::class);
        $wrappedDriver->expects($this->once())
            ->method('createTransportMessage');
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);

        $driver = new OptionalListenerDriver($wrappedDriver, $optionalListenerManager);
        $driver->createTransportMessage();
    }

    public function testSendWithEnabledListeners(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $message = new Message();
        $wrappedDriver = $this->createMock(DriverInterface::class);
        $wrappedDriver->expects($this->once())
            ->method('send')
            ->with(
                $queue,
                $this->callback(function (Message $message) {
                    $this->assertEquals([], $message->getProperties());

                    return true;
                })
            );
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);
        $optionalListenerManager->expects($this->once())
            ->method('getDisabledListeners')
            ->willReturn([]);

        $driver = new OptionalListenerDriver($wrappedDriver, $optionalListenerManager);
        $driver->send($queue, $message);
    }

    public function testSendWithDisabledListeners(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $message = new Message();
        $wrappedDriver = $this->createMock(DriverInterface::class);
        $wrappedDriver->expects($this->once())
            ->method('send')
            ->with(
                $queue,
                $this->callback(function (Message $message) {
                    $this->assertEquals(
                        [
                            OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS =>
                                '["oro_search.index_listener"]',
                        ],
                        $message->getProperties()
                    );

                    return true;
                })
            );
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);
        $optionalListenerManager->expects($this->once())
            ->method('getDisabledListeners')
            ->willReturn(['oro_search.index_listener']);

        $driver = new OptionalListenerDriver($wrappedDriver, $optionalListenerManager);
        $driver->send($queue, $message);
    }
}
