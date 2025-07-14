<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\EventListener\MaintenanceListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MaintenanceListenerTest extends TestCase
{
    private WebsocketClientInterface&MockObject $websocketClient;
    private ConnectionChecker&MockObject $connectionChecker;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private MaintenanceListener $publisher;

    #[\Override]
    protected function setUp(): void
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

    public function testOnModeOn(): void
    {
        $expectedUserId = 0;

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects(self::once())
            ->method('publish')
            ->with('oro/maintenance', ['isOn' => true, 'userId' => $expectedUserId]);
        $this->tokenAccessor->expects(self::once())
            ->method('getUserId')
            ->willReturn($expectedUserId);

        $this->publisher->onModeOn();
    }

    public function testOnModeOnNoConnection(): void
    {
        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient->expects(self::never())
            ->method(self::anything());

        $this->tokenAccessor->expects(self::never())
            ->method(self::anything());

        $this->publisher->onModeOn();
    }

    public function testOnModeOff(): void
    {
        $expectedUserId = 42;

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(true);

        $this->websocketClient->expects(self::once())
            ->method('publish')
            ->with('oro/maintenance', ['isOn' => false, 'userId' => $expectedUserId]);
        $this->tokenAccessor->expects(self::once())
            ->method('getUserId')
            ->willReturn($expectedUserId);

        $this->publisher->onModeOff();
    }

    public function testOnModeOffNoConnection(): void
    {
        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->willReturn(false);

        $this->websocketClient->expects(self::never())
            ->method(self::anything());

        $this->tokenAccessor->expects(self::never())
            ->method(self::anything());

        $this->publisher->onModeOff();
    }
}
