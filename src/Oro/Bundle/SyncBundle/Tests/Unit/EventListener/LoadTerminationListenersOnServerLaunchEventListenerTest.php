<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\Tests\Unit\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Oro\Bundle\SyncBundle\EventListener\LoadTerminationListenersOnServerLaunchEventListener;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class LoadTerminationListenersOnServerLaunchEventListenerTest extends TestCase
{
    public function testOnServerLaunchedLoadsTerminationListeners(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects(self::once())
            ->method('getListeners')
            ->with(ConsoleEvents::TERMINATE)
            ->willReturn([]);

        $listener = new LoadTerminationListenersOnServerLaunchEventListener($eventDispatcher);

        $loop = $this->createMock(LoopInterface::class);
        $server = $this->createMock(ServerInterface::class);
        $serverLaunchedEvent = new ServerLaunchedEvent($loop, $server, false);

        $listener->onServerLaunched($serverLaunchedEvent);
    }
}
