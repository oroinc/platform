<?php

namespace Oro\Bundle\SyncBundle\Client\Factory;

use Gos\Component\WebSocketClient\Wamp\Client as GosClient;

/**
 * Creates websocket server client provided by Gos WebSocketClient component.
 */
class GosClientFactory implements GosClientFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createGosClient(
        string $host,
        string $port,
        bool $secured = false,
        ?string $origin = null
    ): GosClient {
        return new GosClient($host, $port, $secured, $origin);
    }
}
