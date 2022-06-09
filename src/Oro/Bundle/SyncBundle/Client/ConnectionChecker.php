<?php

namespace Oro\Bundle\SyncBundle\Client;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
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

    private ?ApplicationState $applicationState = null;

    private ?bool $isConnected = null;

    public function __construct(WebsocketClientInterface $client)
    {
        $this->client = $client;
        $this->logger = new NullLogger();
    }

    public function setApplicationState(ApplicationState $applicationState): void
    {
        $this->applicationState = $applicationState;
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
                if ($this->applicationState instanceof ApplicationState
                    && $this->applicationState->isInstalled()
                ) {
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
