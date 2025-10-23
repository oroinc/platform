<?php

namespace Oro\Bundle\SyncBundle\WebSocket;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Socket\ServerInterface;

/**
 * Gracefully stops the Websocket server.
 */
class WebsocketServerStopper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly PeriodicRegistry $periodicRegistry,
        private readonly ServerPushHandlerRegistry $serverPushHandlerRegistry
    ) {
        $this->setLogger(new NullLogger());
    }

    /**
     * @throws \Throwable
     */
    public function stopServer(ServerInterface $websocketServer, LoopInterface $eventLoop): void
    {
        $this->logger->notice('Stopping server ...');

        foreach ($this->serverPushHandlerRegistry->getPushers() as $handler) {
            $handler->close();

            $this->logger->info(
                'Stopped {push_handler_name} push handler',
                ['push_handler_name' => $handler->getName()]
            );
        }

        $websocketServer->emit('end');
        $websocketServer->close();

        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
            if ($periodic instanceof TimerInterface) {
                $eventLoop->cancelTimer($periodic);
            }
        }

        $eventLoop->stop();

        $this->logger->notice('Server stopped!');
    }
}
