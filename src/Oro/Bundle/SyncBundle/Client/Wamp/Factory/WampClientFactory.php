<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;

/**
 * Creates websocket server client.
 */
class WampClientFactory implements WampClientFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createClient(WebsocketClientParametersProviderInterface $clientParametersProvider): WampClient
    {
        $options = $clientParametersProvider->getContextOptions();

        return new WampClient(
            $clientParametersProvider->getHost(),
            $clientParametersProvider->getPort(),
            $clientParametersProvider->getTransport(),
            $options ? ['ssl' => $options] : [],
            // We don't have to check origin when connecting from backend.
            '127.0.0.1'
        );
    }
}
