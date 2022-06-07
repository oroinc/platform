<?php

namespace Oro\Bundle\SyncBundle\Client;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\ResettableInterface;

/**
 * Checks connection with websocket server
 */
class ConnectionChecker implements LoggerAwareInterface, ResettableInterface
{
    use LoggerAwareTrait;

    private WebsocketClientInterface $client;

    private bool $applicationInstalled = false;

    private ?bool $isConnected = null;

    public function __construct(WebsocketClientInterface $client)
    {
        $this->client = $client;
        $this->logger = new NullLogger();
    }

    public function setApplicationInstalled(?bool $applicationInstalled)
    {
        $this->applicationInstalled = (bool)$applicationInstalled;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        if ($this->isConnected === null) {
            try {
                $this->client->connect();
                $this->isConnected = $this->client->isConnected();
            } catch (\Throwable $exception) {
                if ($this->applicationInstalled) {
                    $this->logger->error(
                        'Failed to connect to websocket server: {message}',
                        ['message' => $exception->getMessage(), 'e' => $exception]
                    );
                }

                $this->isConnected = false;
            }
        }

        return $this->isConnected;
    }

    public function reset(): void
    {
        $this->isConnected = null;
    }
}
