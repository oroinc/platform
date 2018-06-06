<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Gos\Component\WebSocketClient\Wamp\Client as GosClient;
use Oro\Bundle\SyncBundle\Client\Factory\GosClientFactoryInterface;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;

/**
 * Basic websocket client.
 */
class WebsocketClient implements WebsocketClientInterface
{
    /**
     * @var GosClient
     */
    private $gosClient;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var bool
     */
    private $secured;

    /**
     * @var bool
     */
    private $origin;

    /**
     * @var GosClientFactoryInterface
     */
    private $gosClientFactory;

    /**
     * @param GosClientFactoryInterface $gosClientFactory
     * @param string                    $host
     * @param string                    $port
     * @param bool                      $secured
     * @param null|string               $origin
     */
    public function __construct(
        GosClientFactoryInterface $gosClientFactory,
        string $host,
        string $port,
        bool $secured = false,
        ?string $origin = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->secured = $secured;
        $this->origin = $origin;
        $this->gosClientFactory = $gosClientFactory;
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    public function connect(string $target = '/'): ?string
    {
        return $this->getGosClient()->connect($target);
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        return $this->getGosClient()->disconnect();
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return $this->getGosClient()->isConnected();
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     * @throws ValidationFailedException
     */
    public function publish(string $topicUri, $payload, array $exclude = [], array $eligible = []): bool
    {
        $this->validatePayload($payload);

        $this->getGosClient()->publish($topicUri, $payload, $exclude, $eligible);

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    public function prefix(string $prefix, string $uri): bool
    {
        $this->getGosClient()->prefix($prefix, $uri);

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    public function call(string $procUri, array $arguments = []): bool
    {
        $this->getGosClient()->call($procUri, $arguments);

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     * @throws ValidationFailedException
     */
    public function event(string $topicUri, $payload): bool
    {
        $this->validatePayload($payload);

        $this->getGosClient()->event($topicUri, $payload);

        return true;
    }

    /**
     * @return GosClient
     */
    private function getGosClient(): GosClient
    {
        if (!$this->gosClient) {
            $this->gosClient = $this->gosClientFactory->createGosClient(
                $this->host,
                $this->port,
                $this->secured,
                $this->origin
            );
        }

        return $this->gosClient;
    }

    /**
     * @param mixed $payload
     *
     * @throws ValidationFailedException
     */
    private function validatePayload($payload): void
    {
        $encodedJson = json_encode($payload);
        if ($encodedJson === false && json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationFailedException(json_last_error_msg());
        }
    }
}
