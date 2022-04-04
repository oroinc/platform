<?php

namespace Oro\Bundle\SyncBundle\Client;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
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

    private ApplicationState $applicationState;

    public function __construct(WebsocketClientInterface $client, ApplicationState $applicationState)
    {
        $this->client = $client;
        $this->applicationState = $applicationState;
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
            if ($this->applicationState->isInstalled()) {
                $this->logger->error(
                    'Failed to connect to websocket server: {message}',
                    ['message' => $exception->getMessage(), 'e' => $exception]
                );
            }

            return false;
        }

        return $this->client->isConnected();
    }
}
