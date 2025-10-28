<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\WebSocket;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Oro\Bundle\SyncBundle\Tests\Unit\Stub\PeriodicStub;
use Oro\Bundle\SyncBundle\WebSocket\WebsocketServerStopper;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;

final class WebsocketServerStopperTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private PeriodicRegistry $periodicRegistry;

    private ServerPushHandlerRegistry $serverPushHandlerRegistry;

    private WebsocketServerStopper $stopper;

    protected function setUp(): void
    {
        $this->periodicRegistry = new PeriodicRegistry();
        $this->serverPushHandlerRegistry = new ServerPushHandlerRegistry();

        $this->stopper = new WebsocketServerStopper(
            $this->periodicRegistry,
            $this->serverPushHandlerRegistry
        );
        $this->setUpLoggerMock($this->stopper);
    }

    public function testStopServerClosesAllPushHandlers(): void
    {
        $pusher1 = $this->createMock(ServerPushHandlerInterface::class);
        $pusher1->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('zmq');
        $pusher1->expects(self::once())
            ->method('close');

        $pusher2 = $this->createMock(ServerPushHandlerInterface::class);
        $pusher2->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('amqp');
        $pusher2->expects(self::once())
            ->method('close');

        $this->serverPushHandlerRegistry->addPushHandler($pusher1);
        $this->serverPushHandlerRegistry->addPushHandler($pusher2);

        $websocketServer = $this->createMock(ServerInterface::class);
        $websocketServer->expects(self::once())
            ->method('emit')
            ->with('end');
        $websocketServer->expects(self::once())
            ->method('close');

        $eventLoop = $this->createMock(LoopInterface::class);
        $eventLoop->expects(self::never())
            ->method('cancelTimer');
        $eventLoop->expects(self::once())
            ->method('stop');

        $this->stopper->stopServer($websocketServer, $eventLoop);
    }

    public function testStopServerCancelsAllPeriodics(): void
    {
        $periodic1 = new PeriodicStub();
        $periodic2 = new PeriodicStub();

        $this->periodicRegistry->addPeriodic($periodic1);
        $this->periodicRegistry->addPeriodic($periodic2);

        $websocketServer = $this->createMock(ServerInterface::class);
        $websocketServer->expects(self::once())
            ->method('emit')
            ->with('end');
        $websocketServer->expects(self::once())
            ->method('close');

        $eventLoop = $this->createMock(LoopInterface::class);
        $eventLoop->expects(self::exactly(2))
            ->method('cancelTimer')
            ->with(
                self::logicalOr(
                    self::identicalTo($periodic1),
                    self::identicalTo($periodic2)
                )
            );
        $eventLoop->expects(self::once())
            ->method('stop');

        $this->stopper->stopServer($websocketServer, $eventLoop);
    }

    public function testStopServerWithNoPushersAndNoPeriodics(): void
    {
        $websocketServer = $this->createMock(ServerInterface::class);
        $websocketServer->expects(self::once())
            ->method('emit')
            ->with('end');
        $websocketServer->expects(self::once())
            ->method('close');

        $eventLoop = $this->createMock(LoopInterface::class);
        $eventLoop->expects(self::never())
            ->method('cancelTimer');
        $eventLoop->expects(self::once())
            ->method('stop');

        $this->stopper->stopServer($websocketServer, $eventLoop);
    }
}
