<?php

namespace Oro\Bundle\SyncBundle\Client;

/**
 * Interface for websocket clients.
 */
interface WebsocketClientInterface
{
    public function connect(): ?string;

    public function disconnect(): bool;

    public function isConnected(): bool;

    /**
     * @param string $topicUri
     * @param mixed  $payload
     * @param array  $exclude
     * @param array  $eligible
     *
     * @return bool
     */
    public function publish(string $topicUri, $payload, array $exclude = [], array $eligible = []): bool;

    public function prefix(string $prefix, string $uri): bool;

    public function call(string $procUri, array $arguments = []): bool;

    /**
     * @param string $topicUri
     * @param mixed  $payload
     *
     * @return bool
     */
    public function event(string $topicUri, $payload): bool;
}
