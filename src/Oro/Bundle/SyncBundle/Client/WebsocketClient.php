<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Component\WebSocketClient\Exception\BadResponseException;
use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\Wamp\Factory\ClientAttributes;
use Oro\Bundle\SyncBundle\Client\Wamp\Factory\WampClientFactoryInterface;
use Oro\Bundle\SyncBundle\Client\Wamp\WampClient;
use Oro\Bundle\SyncBundle\Exception\ValidationFailedException;

/**
 * Websocket client with ticket-authentication.
 */
class WebsocketClient implements WebsocketClientInterface
{
    /** @var WampClientFactoryInterface */
    private $wampClientFactory;

    /** @var ClientAttributes */
    private $clientAttributes;

    /** @var TicketProviderInterface */
    private $ticketProvider;

    /** @var WampClient */
    private $wampClient;

    /**
     * @param WampClientFactoryInterface $wampClientFactory
     * @param ClientAttributes $clientAttributes
     * @param TicketProviderInterface $ticketProvider
     */
    public function __construct(
        WampClientFactoryInterface $wampClientFactory,
        ClientAttributes $clientAttributes,
        TicketProviderInterface $ticketProvider
    ) {
        $this->wampClientFactory = $wampClientFactory;
        $this->clientAttributes = $clientAttributes;
        $this->ticketProvider = $ticketProvider;
    }

    /**
     * {@inheritDoc}
     *
     * @throws WebsocketException
     * @throws BadResponseException
     */
    public function connect(): ?string
    {
        $urlInfo = parse_url($this->clientAttributes->getPath()) + ['path' => '', 'query' => ''];
        parse_str($urlInfo['query'], $query);
        $query['ticket'] = $this->ticketProvider->generateTicket();
        $pathWithTicket = sprintf('%s?%s', $urlInfo['path'], http_build_query($query));

        return $this->getWampClient()->connect($pathWithTicket);
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        return $this->getWampClient()->disconnect();
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return $this->getWampClient()->isConnected();
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

        $this->getWampClient()->publish($topicUri, $payload, $exclude, $eligible);

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
        $this->getWampClient()->prefix($prefix, $uri);

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
        $this->getWampClient()->call($procUri, $arguments);

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

        $this->getWampClient()->event($topicUri, $payload);

        return true;
    }

    /**
     * @return WampClient
     */
    private function getWampClient(): WampClient
    {
        if (!$this->wampClient) {
            $this->wampClient = $this->wampClientFactory->createClient($this->clientAttributes);
        }

        return $this->wampClient;
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
