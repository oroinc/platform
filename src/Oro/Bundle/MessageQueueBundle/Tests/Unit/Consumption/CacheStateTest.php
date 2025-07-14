<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\StateDriverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheStateTest extends TestCase
{
    private StateDriverInterface&MockObject $driver;
    private CacheState $cacheState;

    #[\Override]
    protected function setUp(): void
    {
        $this->driver = $this->createMock(StateDriverInterface::class);

        $this->cacheState = new CacheState($this->driver);
    }

    public function testRenewChangeDate(): void
    {
        $this->driver->expects(self::once())
            ->method('setChangeStateDate')
            ->with(self::isInstanceOf(\DateTime::class));

        $this->cacheState->renewChangeDate();
    }

    public function testGetChangeDate(): void
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn($date);

        $this->assertSame($date, $this->cacheState->getChangeDate());
    }
}
