<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\EventListener\MaintenanceListener;
use Oro\Bundle\SyncBundle\Wamp\TopicPublisher;
use Psr\Log\LoggerInterface;

class MaintenanceListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $topicPublisher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $tokenAccessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    /** @var MaintenanceListener */
    private $publisher;

    protected function setUp()
    {
        $this->topicPublisher = $this->createMock(TopicPublisher::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->publisher = new MaintenanceListener(
            $this->topicPublisher,
            $this->tokenAccessor,
            $this->logger
        );
    }

    public function testOnModeOn()
    {
        $expectedUserId = 0;
        $this->topicPublisher
            ->expects($this->once())
            ->method('send')
            ->with('oro/maintenance', array('isOn' => true, 'userId' => $expectedUserId));
        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->will($this->returnValue($expectedUserId));

        $this->publisher->onModeOn();
    }

    public function testOnModeOff()
    {
        $expectedUserId = 42;
        $this->topicPublisher
            ->expects($this->once())
            ->method('send')
            ->with('oro/maintenance', array('isOn' => false, 'userId' => $expectedUserId));
        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->will($this->returnValue($expectedUserId));

        $this->publisher->onModeOff();
    }
}
