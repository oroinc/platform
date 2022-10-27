<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Platform;

use Oro\Bundle\MessageQueueBundle\Platform\OptionalListenerDriver;
use Oro\Bundle\MessageQueueBundle\Platform\OptionalListenerDriverFactory;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

class OptionalListenerDriverFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testFactory()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $config = $this->createMock(Config::class);
        $driver = $this->createMock(DriverInterface::class);
        $driverFactory = $this->createMock(DriverFactoryInterface::class);
        $driverFactory
            ->expects($this->once())
            ->method('create')->with($connection, $config)
            ->willReturn($driver);
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);

        $factory = new OptionalListenerDriverFactory($driverFactory, $optionalListenerManager);

        $wrappedDriver = $factory->create($connection, $config);

        $this->assertInstanceOf(DriverInterface::class, $wrappedDriver);
        $this->assertInstanceOf(OptionalListenerDriver::class, $wrappedDriver);
    }
}
