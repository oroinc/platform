<?php

namespace Oro\Bundle\SyncBundle\Client;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Checks connection with websocket server
 */
class ConnectionChecker implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private WebsocketClientInterface $client;

    public function __construct(WebsocketClientInterface $client)
    {
        $this->client = $client;
        $this->logger = new NullLogger();
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        try {
            $this->client->connect();
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Failed to connect to websocket server: {message}',
                ['message' => $exception->getMessage(), 'e' => $exception]
            );

            return false;
        }

        return $this->client->isConnected();
    }
}
