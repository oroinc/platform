<?php

namespace Oro\Bundle\SyncBundle\Client;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\SyncBundle\Provider\WebsocketClientParametersProviderInterface;
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

    private ApplicationState $applicationState;

    private WebsocketClientParametersProviderInterface $websocketClientParametersProvider;

    private ?bool $isConnected = null;

    public function __construct(
        WebsocketClientInterface $client,
        ApplicationState $applicationState,
        WebsocketClientParametersProviderInterface $websocketClientParametersProvider
    ) {
        $this->client = $client;
        $this->applicationState = $applicationState;
        $this->websocketClientParametersProvider = $websocketClientParametersProvider;
        $this->logger = new NullLogger();
    }

    public function checkConnection(): bool
    {
        if ($this->isConfigured() === false) {
            return false;
        }

        if ($this->isConnected === null) {
            try {
                $this->client->connect();
                $this->isConnected = $this->client->isConnected();
            } catch (\Throwable $exception) {
                if ($this->applicationState->isInstalled()) {
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

    public function isConfigured(): bool
    {
        $host = $this->websocketClientParametersProvider->getHost();

        return $host !== '' && $host !== 'null';
    }

    public function reset(): void
    {
        $this->isConnected = null;
    }
}
