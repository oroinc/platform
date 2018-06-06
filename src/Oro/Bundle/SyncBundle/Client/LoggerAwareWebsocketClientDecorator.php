<?php

namespace Oro\Bundle\SyncBundle\Client;

use Gos\Component\WebSocketClient\Exception\WebsocketException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Adds logging facilities to websocket client.
 */
class LoggerAwareWebsocketClientDecorator extends AbstractWebsocketClientDecorator implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param WebsocketClientInterface $decoratedClient
     */
    public function __construct(WebsocketClientInterface $decoratedClient)
    {
        parent::__construct($decoratedClient);

        $this->setLogger(new NullLogger());
    }

    /**
     * {@inheritDoc}
     */
    public function connect(string $target = '/'): ?string
    {
        $result = null;

        try {
            $result = $this->decoratedClient->connect($target);

            $this->logger->debug('Connected to websocket server');
        } catch (WebsocketException $e) {
            $this->logger->error('Could not connect to websocket server', [$e]);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect(): bool
    {
        $result = $this->decoratedClient->disconnect();

        if ($result) {
            $this->logger->debug('Disconnected from websocket server');
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function publish(string $topicUri, $payload, array $exclude = [], array $eligible = []): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->publish($topicUri, $payload, $exclude, $eligible);

            $this->logger->debug(sprintf('PUBLISH in %s websocket server', $topicUri));
        } catch (WebsocketException $e) {
            $this->logger->error('Could not send data to websocket server', [$e]);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function prefix(string $prefix, string $uri): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->prefix($prefix, $uri);

            $this->logger->debug(sprintf('PREFIX %s in %s websocket server', $prefix, $uri));
        } catch (WebsocketException $e) {
            $this->logger->error('Could not send data to websocket server', [$e]);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function call(string $procUri, array $arguments = []): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->call($procUri, $arguments);

            $this->logger->debug(sprintf('CALL in %s websocket server', $procUri));
        } catch (WebsocketException $e) {
            $this->logger->error('Could not send data to websocket server', [$e]);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function event(string $topicUri, string $payload): bool
    {
        $result = false;

        try {
            $result = $this->decoratedClient->event($topicUri, $payload);

            $this->logger->debug(sprintf('EVENT in %s websocket server', $topicUri));
        } catch (WebsocketException $e) {
            $this->logger->error('Could not send data to websocket server', [$e]);
        }

        return $result;
    }
}
