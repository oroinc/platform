<?php

namespace Oro\Bundle\SyncBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SyncBundle\Client\ConnectionChecker;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;

/**
 * Handles maintenance mode state changes and broadcasts them to connected clients via WebSocket.
 *
 * This listener responds to maintenance mode activation and deactivation events, and notifies
 * all connected users about the maintenance mode status. It checks the WebSocket connection
 * availability before attempting to broadcast the message, ensuring graceful handling when
 * the WebSocket server is unavailable. The maintenance status is sent along with the user ID
 * of the user who triggered the mode change.
 */
class MaintenanceListener
{
    /**
     * @var WebsocketClientInterface
     */
    private $client;

    /**
     * @var ConnectionChecker
     */
    private $connectionChecker;

    /**
     * @var TokenAccessorInterface
     */
    private $tokenAccessor;

    public function __construct(
        WebsocketClientInterface $client,
        ConnectionChecker $connectionChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->client = $client;
        $this->connectionChecker = $connectionChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function onModeOn()
    {
        $this->onMode(true);
    }

    public function onModeOff()
    {
        $this->onMode(false);
    }

    private function onMode(bool $isOn)
    {
        if (!$this->connectionChecker->checkConnection()) {
            return;
        }

        $userId = $this->tokenAccessor->getUserId();

        $this->client->publish('oro/maintenance', ['isOn' => $isOn, 'userId' => $userId]);
    }
}
