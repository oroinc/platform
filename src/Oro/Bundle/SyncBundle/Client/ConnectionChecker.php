<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Component\WebSocketClient\Exception\WebsocketException;

/**
 * Checks connection with websocket server
 */
class ConnectionChecker
{
    /** @var WebsocketClientInterface */
    private $client;

    /**
     * @param WebsocketClientInterface $client
     */
    public function __construct(WebsocketClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        try {
            $this->client->connect();
        } catch (WebsocketException $e) {
            return false;
        }
        return $this->client->isConnected();
    }
}
