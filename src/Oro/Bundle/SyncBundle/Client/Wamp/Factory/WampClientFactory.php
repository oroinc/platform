<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Creates websocket server client.
 */
class WampClientFactory implements WampClientFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function createClient(WebsocketClientParametersProviderInterface $clientParametersProvider): WampClient
    {
        $options = $clientParametersProvider->getContextOptions();

        $wampClient = new WampClient(
            $clientParametersProvider->getHost(),
            $clientParametersProvider->getPort(),
            $clientParametersProvider->getTransport(),
            $options ? ['ssl' => $options] : [],
            // We don't have to check origin when connecting from backend.
            '127.0.0.1',
            $clientParametersProvider->getUserAgent(),
        );

        $wampClient->setLogger($this->logger);

        return $wampClient;
    }
}
