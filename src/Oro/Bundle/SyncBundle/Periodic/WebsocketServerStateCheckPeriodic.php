<?php

namespace Oro\Bundle\SyncBundle\Periodic;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Oro\Bundle\SyncBundle\WebSocket\WebsocketServerStopper;
use Oro\Bundle\SyncBundle\WebsocketServerState\WebsocketServerStateManagerInterface;
use Override;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Periodically checks the specified state of the Websocket server and stops it if the state date has changed.
 */
class WebsocketServerStateCheckPeriodic implements PeriodicInterface, ResetInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?\DateTimeInterface $stateDate;

    private ?LoopInterface $eventLoop = null;

    private ?ServerInterface $websocketServer = null;

    public function __construct(
        private readonly WebsocketServerStateManagerInterface $websocketServerStateManager,
        private readonly WebsocketServerStopper $websocketServerStopper,
        private readonly string $stateId,
        private readonly int $timeout = 1
    ) {
        $this->setLogger(new NullLogger());

        $this->stateDate = $this->websocketServerStateManager->getState($this->stateId) ??
            $this->websocketServerStateManager->updateState($this->stateId);
    }

    /**
     * @throws \Throwable
     */
    #[\Override]
    public function tick(): void
    {
        if (!$this->websocketServer || !$this->eventLoop) {
            return;
        }

        $actualStateDate = $this->websocketServerStateManager->getState($this->stateId);

        if ($this->stateDate->getTimestamp() === $actualStateDate?->getTimestamp()) {
            return;
        }

        $this->logger->notice(
            'Websocket server state "{state_id}" is changed, the server will be stopped',
            [
                'state_id' => $this->stateId,
            ]
        );

        $this->websocketServerStopper->stopServer($this->websocketServer, $this->eventLoop);
        $this->reset();
    }

    #[\Override]
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function onServerLaunched(ServerLaunchedEvent $event): void
    {
        $this->eventLoop = $event->getEventLoop();
        $this->websocketServer = $event->getServer();
    }

    #[Override]
    public function reset(): void
    {
        $this->eventLoop = null;
        $this->websocketServer = null;
    }
}
