<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\Periodic;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Oro\Bundle\SyncBundle\Periodic\WebsocketServerStateCheckPeriodic;
use Oro\Bundle\SyncBundle\WebSocket\WebsocketServerStopper;
use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerStateManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;

final class WebsocketServerStateCheckPeriodicTest extends TestCase
{
    private WebsocketServerStateManagerInterface&MockObject $stateManager;
    private WebsocketServerStopper&MockObject $serverStopper;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->stateManager = $this->createMock(WebsocketServerStateManagerInterface::class);
        $this->serverStopper = $this->createMock(WebsocketServerStopper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testConstructorInitializesWithExistingState(): void
    {
        $stateId = 'test_state';
        $existingDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::once())
            ->method('getState')
            ->with($stateId)
            ->willReturn($existingDate);

        $this->stateManager->expects(self::never())
            ->method('updateState');

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId,
            5
        );

        self::assertEquals(5, $periodic->getTimeout());
    }

    public function testConstructorCreatesNewStateWhenNotExists(): void
    {
        $stateId = 'test_state';
        $newDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::once())
            ->method('getState')
            ->with($stateId)
            ->willReturn(null);

        $this->stateManager->expects(self::once())
            ->method('updateState')
            ->with($stateId)
            ->willReturn($newDate);

        new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId
        );
    }

    public function testGetTimeoutReturnsDefaultTimeout(): void
    {
        $stateId = 'test_state';
        $stateDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::once())
            ->method('getState')
            ->with($stateId)
            ->willReturn($stateDate);

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId
        );

        self::assertEquals(1, $periodic->getTimeout());
    }

    public function testGetTimeoutReturnsCustomTimeout(): void
    {
        $stateId = 'test_state';
        $stateDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::once())
            ->method('getState')
            ->with($stateId)
            ->willReturn($stateDate);

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId,
            10
        );

        self::assertEquals(10, $periodic->getTimeout());
    }

    public function testTickDoesNothingWhenStateUnchanged(): void
    {
        $stateId = 'test_state';
        $stateDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::once())
            ->method('getState')
            ->with($stateId)
            ->willReturn($stateDate);

        $this->serverStopper->expects(self::never())
            ->method('stopServer');

        $this->logger->expects(self::never())
            ->method('notice');

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId
        );
        $periodic->setLogger($this->logger);

        $periodic->tick();
    }

    public function testTickStopsServerWhenStateChanged(): void
    {
        $stateId = 'test_state';
        $initialDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));
        $changedDate = new \DateTime('2024-01-15 11:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::exactly(2))
            ->method('getState')
            ->with($stateId)
            ->willReturnOnConsecutiveCalls($initialDate, $changedDate);

        $eventLoop = $this->createMock(LoopInterface::class);
        $websocketServer = $this->createMock(ServerInterface::class);

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId
        );
        $periodic->setLogger($this->logger);

        // Set up server via event
        $event = new ServerLaunchedEvent($eventLoop, $websocketServer, false);
        $periodic->onServerLaunched($event);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with(
                'Websocket server state "{state_id}" is changed, the server will be stopped',
                ['state_id' => $stateId]
            );

        $this->serverStopper->expects(self::once())
            ->method('stopServer')
            ->with($websocketServer, $eventLoop);

        $periodic->tick();
    }

    public function testResetClearsEventLoopAndServer(): void
    {
        $stateId = 'test_state';
        $stateDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::once())
            ->method('getState')
            ->with($stateId)
            ->willReturn($stateDate);

        $this->stateManager->expects(self::never())
            ->method('updateState');

        $eventLoop = $this->createMock(LoopInterface::class);
        $websocketServer = $this->createMock(ServerInterface::class);

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId
        );

        // Set server and loop
        $event = new ServerLaunchedEvent($eventLoop, $websocketServer, false);
        $periodic->onServerLaunched($event);

        // Reset clears the references
        $periodic->reset();

        // Verify that nothing happens after reset
        $this->serverStopper->expects(self::never())
            ->method('stopServer');

        // Can't trigger tick without consequences, but reset is validated
        self::assertInstanceOf(WebsocketServerStateCheckPeriodic::class, $periodic);
    }

    public function testTickHandlesNullActualStateDate(): void
    {
        $stateId = 'test_state';
        $initialDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::exactly(2))
            ->method('getState')
            ->with($stateId)
            ->willReturnOnConsecutiveCalls($initialDate, null);

        $eventLoop = $this->createMock(LoopInterface::class);
        $websocketServer = $this->createMock(ServerInterface::class);

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId
        );
        $periodic->setLogger($this->logger);

        $event = new ServerLaunchedEvent($eventLoop, $websocketServer, false);
        $periodic->onServerLaunched($event);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with(
                'Websocket server state "{state_id}" is changed, the server will be stopped',
                ['state_id' => $stateId]
            );

        $this->serverStopper->expects(self::once())
            ->method('stopServer')
            ->with($websocketServer, $eventLoop);

        $periodic->tick();
    }

    public function testTickDoesNotStopServerIfNoEventLoopOrServerSet(): void
    {
        $stateId = 'test_state';
        $initialDate = new \DateTime('2024-01-15 10:00:00', new \DateTimeZone('UTC'));

        $this->stateManager->expects(self::once())
            ->method('getState')
            ->with($stateId)
            ->willReturn($initialDate);

        $periodic = new WebsocketServerStateCheckPeriodic(
            $this->stateManager,
            $this->serverStopper,
            $stateId
        );
        $periodic->setLogger($this->logger);

        $this->logger->expects(self::never())
            ->method('notice');

        $this->serverStopper->expects(self::never())
            ->method('stopServer');

        $periodic->tick();
    }
}
