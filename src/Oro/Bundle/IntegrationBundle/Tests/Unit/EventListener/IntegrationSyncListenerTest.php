<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\EventListener;

use Oro\Bundle\IntegrationBundle\Event\SyncEvent;
use Oro\Bundle\IntegrationBundle\EventListener\IntegrationSyncListener;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\TransactionDriverStub;
use Oro\Component\MessageQueue\Client\DriverInterface;

class IntegrationSyncListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testSyncBefore()
    {
        /** @var TransactionDriverStub|\PHPUnit_Framework_MockObject_MockObject $driver */
        $driver = $this->createMock(TransactionDriverStub::class);
        $driver->expects($this->once())
            ->method('startTransaction');

        $listener = new IntegrationSyncListener($driver);
        $listener->syncBefore(new SyncEvent('', []));
    }

    public function testSyncBeforeInvalid()
    {
        /** @var DriverInterface|\PHPUnit_Framework_MockObject_MockObject $driver */
        $driver = $this->getMockBuilder(DriverInterface::class)
            ->setMethods(['startTransaction', 'createTransportMessage', 'send', 'createQueue', 'getConfig'])
            ->getMock();
        $driver->expects($this->never())
            ->method('startTransaction');

        $listener = new IntegrationSyncListener($driver);
        $listener->syncBefore(new SyncEvent('', []));
    }

    public function testSyncAfter()
    {
        /** @var TransactionDriverStub|\PHPUnit_Framework_MockObject_MockObject $driver */
        $driver = $this->createMock(TransactionDriverStub::class);
        $driver->expects($this->once())
            ->method('commit');

        $listener = new IntegrationSyncListener($driver);
        $listener->syncAfter(new SyncEvent('', []));
    }

    public function testSyncAfterInvalid()
    {
        /** @var DriverInterface|\PHPUnit_Framework_MockObject_MockObject $driver */
        $driver = $this->getMockBuilder(DriverInterface::class)
            ->setMethods(['commit', 'createTransportMessage', 'send', 'createQueue', 'getConfig'])
            ->getMock();
        $driver->expects($this->never())
            ->method('commit');

        $listener = new IntegrationSyncListener($driver);
        $listener->syncAfter(new SyncEvent('', []));
    }
}
