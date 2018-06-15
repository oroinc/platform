<?php

namespace Oro\Bundle\SyncBundle\Client;

/**
 * Interface for websocket clients.
 */
interface WebsocketClientInterface
{
    /**
     * @return string
     */
    public function connect(): ?string;

    /**
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * @return bool
     */
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

    /**
     * @param string $prefix
     * @param string $uri
     *
     * @return bool
     */
    public function prefix(string $prefix, string $uri): bool;

    /**
     * @param string $procUri
     * @param array  $arguments
     *
     * @return bool
     */
    public function call(string $procUri, array $arguments = []): bool;

    /**
     * @param string $topicUri
     * @param mixed  $payload
     *
     * @return bool
     */
    public function event(string $topicUri, $payload): bool;
}
