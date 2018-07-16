<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\StateDriverInterface;

class CacheStateTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|StateDriverInterface */
    private $driver;

    /** @var CacheState */
    private $cacheState;

    protected function setUp()
    {
        $this->driver = $this->createMock(StateDriverInterface::class);

        $this->cacheState = new CacheState($this->driver);
    }

    public function testRenewChangeDate()
    {
        $this->driver->expects(self::once())
            ->method('setChangeStateDate')
            ->with(self::isInstanceOf(\DateTime::class));

        $this->cacheState->renewChangeDate();
    }

    public function testGetChangeDate()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->driver->expects(self::once())
            ->method('getChangeStateDate')
            ->willReturn($date);

        $this->assertSame($date, $this->cacheState->getChangeDate());
    }
}
