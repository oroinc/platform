<?php

namespace Oro\Bundle\SyncBundle\Wamp;

/**
 * Interface to describe websocket factories
 */
interface WebSocketClientFactoryInterface
{
    /**
     * @param WebSocketClientAttributes $clientAttributes
     * @return WebSocket
     */
    public function create(WebSocketClientAttributes $clientAttributes): WebSocket;
}
