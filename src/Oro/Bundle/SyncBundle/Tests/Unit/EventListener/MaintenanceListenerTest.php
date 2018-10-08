<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\EventListener\MaintenanceListener;

class MaintenanceListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebsocketClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websocketClient;

    /**
     * @var ConnectionChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connectionChecker;

    /**
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenAccessor;

    /**
     * @var MaintenanceListener
     */
    private $publisher;

    protected function setUp()
    {
        $this->websocketClient = $this->createMock(WebsocketClientInterface::class);
        $this->connectionChecker = $this->createMock(ConnectionChecker::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->publisher = new MaintenanceListener(
            $this->websocketClient,
            $this->connectionChecker,
            $this->tokenAccessor
        );
    }

    public function testOnModeOn()
    {
        $expectedUserId = 0;

        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

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

    public function testOnModeOnNoConnection()
    {
        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient
            ->expects(self::never())
            ->method(self::anything());

        $this->tokenAccessor
            ->expects(self::never())
            ->method(self::anything());

        $this->publisher->onModeOn();
    }

    public function testOnModeOff()
    {
        $expectedUserId = 42;

        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

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

    public function testOnModeOffNoConnection()
    {
        $this->connectionChecker
            ->expects(self::once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient
            ->expects(self::never())
            ->method(self::anything());

        $this->tokenAccessor
            ->expects(self::never())
            ->method(self::anything());

        $this->publisher->onModeOff();
    }
}
