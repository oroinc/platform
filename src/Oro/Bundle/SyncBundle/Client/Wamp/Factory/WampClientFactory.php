<?php

namespace Oro\Bundle\SyncBundle\Client\Wamp\Factory;

use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
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

    /**
     * {@inheritdoc}
     */
    public function createClient(ClientAttributes $clientAttributes): WampClient
    {
        $options = $clientAttributes->getContextOptions();

        $wampClient = new WampClient(
            $clientAttributes->getHost(),
            $clientAttributes->getPort(),
            $clientAttributes->getTransport(),
            $options ? ['ssl' => $options] : [],
            // We don't have to check origin when connecting from backend.
            '127.0.0.1'
        );

        $wampClient->setUserAgent($clientAttributes->getUserAgent());
        $wampClient->setLogger($this->logger);

        return $wampClient;
    }
}
