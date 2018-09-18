<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;

/**
 * Creates websocket server client.
 */
class WampClientFactory implements WampClientFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createClient(ClientAttributes $clientAttributes): WampClient
    {
        $options = $clientAttributes->getContextOptions();

        return new WampClient(
            $clientAttributes->getHost(),
            $clientAttributes->getPort(),
            $clientAttributes->getTransport(),
            $options ? ['ssl' => $options] : [],
            // We don't have to check origin when connecting from backend.
            '127.0.0.1'
        );
    }
}
