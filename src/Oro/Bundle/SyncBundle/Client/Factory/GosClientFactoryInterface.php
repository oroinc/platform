<?php

namespace Oro\Bundle\SyncBundle\Client\Factory;

use Gos\Component\WebSocketClient\Wamp\Client as GosClient;

/**
 * Interface for Gos websocket server client factories.
 */
interface GosClientFactoryInterface
{
    /**
     * @param string $host
     * @param string $port
     * @param bool $secured
     * @param null|string $origin
     *
     * @return GosClient
     */
    public function createGosClient(
        string $host,
        string $port,
        bool $secured = false,
        ?string $origin = null
    ): GosClient;
}
