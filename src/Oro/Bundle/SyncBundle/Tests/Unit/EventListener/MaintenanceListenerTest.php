<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\EventListener\MaintenanceListener;

class MaintenanceListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsocketClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websocketClient;

    /**
     * @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenAccessor;

    /**
     * @var MaintenanceListener
     */
    private $publisher;

    protected function setUp()
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->publisher = new MaintenanceListener($this->websocketClient, $this->tokenAccessor);
    }

    public function testOnModeOn()
    {
        $expectedUserId = 0;
        $this->websocketClient
            ->expects(self::once())
            ->method('publish')
            ->with('oro/maintenance', ['isOn' => true, 'userId' => $expectedUserId]);
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUserId')
            ->willReturn($expectedUserId);

        $this->publisher->onModeOn();
    }

    public function testOnModeOff()
    {
        $expectedUserId = 42;
        $this->websocketClient
            ->expects(self::once())
            ->method('publish')
            ->with('oro/maintenance', ['isOn' => false, 'userId' => $expectedUserId]);
        $this->tokenAccessor
            ->expects(self::once())
            ->method('getUserId')
            ->willReturn($expectedUserId);

        $this->publisher->onModeOff();
    }
}
