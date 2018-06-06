<?php

namespace Oro\Bundle\SyncBundle\Client;

/**
 * Abstract class used by decorators of WebsocketClient to minimize copy-pasted methods.
 */
abstract class AbstractWebsocketClientDecorator implements WebsocketClientInterface
{
    /**
     * @var WebsocketClientInterface
     */
    protected $decoratedClient;

    /**
     * @param WebsocketClientInterface $decoratedClient
     */
    public function __construct(WebsocketClientInterface $decoratedClient)
    {
        $this->decoratedClient = $decoratedClient;
    }

    /**
     * {@inheritDoc}
     */
    public function connect(string $target = '/'): ?string
    {
        return $this->decoratedClient->connect($target);
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        return $this->decoratedClient->disconnect();
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return $this->decoratedClient->isConnected();
    }

    /**
     * {@inheritDoc}
     */
    public function publish(string $topicUri, $payload, array $exclude = [], array $eligible = []): bool
    {
        return $this->decoratedClient->publish($topicUri, $payload, $exclude, $eligible);
    }

    /**
     * {@inheritDoc}
     */
    public function prefix(string $prefix, string $uri): bool
    {
        return $this->decoratedClient->prefix($prefix, $uri);
    }

    /**
     * {@inheritDoc}
     */
    public function call(string $procUri, array $arguments = []): bool
    {
        return $this->decoratedClient->call($procUri, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function event(string $topicUri, string $payload): bool
    {
        return $this->decoratedClient->event($topicUri, $payload);
    }
}
