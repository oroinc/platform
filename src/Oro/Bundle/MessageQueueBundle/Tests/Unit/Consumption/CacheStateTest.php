<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Consumption;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\StateDriverInterface;

class CacheStateTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|StateDriverInterface */
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
