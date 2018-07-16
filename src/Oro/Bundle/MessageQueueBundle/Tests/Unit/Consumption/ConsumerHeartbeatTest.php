<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\MessageQueueBundle\Consumption\StateDriverInterface;

class ConsumerHeartbeatTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    /** @var ConsumerHeartbeat */
    private $consumerHeartbeat;

    protected function setUp()
    {
        $this->driver = $this->createMock(StateDriverInterface::class);
        $this->consumerHeartbeat= new ConsumerHeartbeat($this->driver, 15);
    }

    public function testTick()
    {
        $this->driver->expects(self::once())
            ->method('setChangeStateDateWithTimeGap')
            ->with(self::isInstanceOf(\DateTime::class));

        $this->consumerHeartbeat->tick();
    }

    public function testIsAliveWithOverdueState()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(new \DateInterval('PT17M'));

        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn($date);

        $this->assertFalse($this->consumerHeartbeat->isAlive());
    }

    public function testIsAliveWithCorrectState()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->sub(new \DateInterval('PT5M'));

        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn($date);

        $this->assertTrue($this->consumerHeartbeat->isAlive());
    }

    public function testIsAliveWithNullStateDate()
    {
        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn(null);

        $this->assertFalse($this->consumerHeartbeat->isAlive());
    }
}
