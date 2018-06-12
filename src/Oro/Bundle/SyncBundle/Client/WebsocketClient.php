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

    /** @var string */
    private $port;

    /** @var bool */
    private $secured;

    /** @var bool */
    private $origin;

    /** @var GosClient */
    private $gosClient;

    /**
     * @param GosClientFactoryInterface $gosClientFactory
     * @param TicketProviderInterface $ticketProvider
     * @param string $host
     * @param string $port
     * @param bool $secured
     * @param null|string $origin
     */
    public function __construct(
        GosClientFactoryInterface $gosClientFactory,
        TicketProviderInterface $ticketProvider,
        string $host,
        string $port,
        bool $secured = false,
        ?string $origin = null
    ) {
        $this->gosClientFactory = $gosClientFactory;
        $this->ticketProvider = $ticketProvider;
        $this->host = $host;
        $this->port = $port;
        $this->secured = $secured;
        $this->origin = $origin;
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    public function connect(string $target = '/'): ?string
    {
        $urlInfo = parse_url($target) + ['path' => '', 'query' => ''];
        parse_str($urlInfo['query'], $query);
        $query['ticket'] = $this->ticketProvider->generateTicket();
        $targetWithTicket = sprintf('%s?%s', $urlInfo['path'], http_build_query($query));

        return $this->getGosClient()->connect($targetWithTicket);
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
                $this->origin
            );
        }

        return $this->gosClient;
    }

    /**
     * @param string $target
     * @throws BadResponseException
     * @throws WebsocketException
     */
    private function ensureClientConnected(string $target = '/')
    {
        if (!$this->isConnected()) {
            $this->connect($target);
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
