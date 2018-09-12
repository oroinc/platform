<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Gos\Component\WebSocketClient\Wamp\Client as GosClient;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\Factory\GosClientFactoryInterface;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;

/**
 * Websocket client with ticket-authentication.
 */
class WebsocketClient implements WebsocketClientInterface
{
    /** @var GosClientFactoryInterface */
    private $gosClientFactory;

    /** @var TicketProviderInterface */
    private $ticketProvider;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var string */
    private $path;

    /** @var bool */
    private $secured;

    /** @var GosClient */
    private $gosClient;

    /**
     * @param GosClientFactoryInterface $gosClientFactory
     * @param TicketProviderInterface $ticketProvider
     * @param string $host
     * @param string|int $port
     * @param string $path
     * @param bool $secured
     */
    public function __construct(
        GosClientFactoryInterface $gosClientFactory,
        TicketProviderInterface $ticketProvider,
        string $host,
        $port,
        string $path = '',
        bool $secured = false
    ) {
        $this->gosClientFactory = $gosClientFactory;
        $this->ticketProvider = $ticketProvider;

        if ('*' === $host) {
            $host = '127.0.0.1';
        }

        $this->host = $host;
        $this->port = (int)$port;
        $this->path = '/' . ltrim($path, '/');
        $this->secured = $secured;
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    public function connect(): ?string
    {
        $urlInfo = parse_url($this->path) + ['path' => '', 'query' => ''];
        parse_str($urlInfo['query'], $query);
        $query['ticket'] = $this->ticketProvider->generateTicket();
        $pathWithTicket = sprintf('%s?%s', $urlInfo['path'], http_build_query($query));

        return $this->getGosClient()->connect($pathWithTicket);
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
        $this->ensureClientConnected();

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
        $this->ensureClientConnected();
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
        $this->ensureClientConnected();
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
        $this->ensureClientConnected();

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
                // We don't have to check origin when connecting from backend.
                '127.0.0.1'
            );
        }

        return $this->gosClient;
    }

    /**
     * @throws BadResponseException
     * @throws WebsocketException
     */
    private function ensureClientConnected(): void
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
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
