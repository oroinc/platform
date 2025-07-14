<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\MessageQueueBundle\Consumption\StateDriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConsumerHeartbeatTest extends TestCase
{
    private StateDriverInterface&MockObject $driver;
    private ConsumerHeartbeat $consumerHeartbeat;

    #[\Override]
    protected function setUp(): void
    {
        $this->driver = $this->createMock(StateDriverInterface::class);

        $this->consumerHeartbeat = new ConsumerHeartbeat($this->driver, 15);
    }

    public function testTick(): void
    {
        $this->driver->expects(self::once())
            ->method('setChangeStateDateWithTimeGap')
            ->with(self::isInstanceOf(\DateTime::class));

        $this->consumerHeartbeat->tick();
    }

    public function testIsAliveWithOverdueState(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(new \DateInterval('PT17M'));

        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn($date);

        $this->assertFalse($this->consumerHeartbeat->isAlive());
    }

    public function testIsAliveWithCorrectState(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(new \DateInterval('PT5M'));

        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn($date);

        $this->assertTrue($this->consumerHeartbeat->isAlive());
    }

    public function testIsAliveWithNullStateDate(): void
    {
        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn(null);

        $this->assertFalse($this->consumerHeartbeat->isAlive());
    }
}
